#!/usr/bin/env bash

set -euo pipefail

gitleaks_bin="${1:-gitleaks}"
expected_version="${GITLEAKS_VERSION:-8.24.3}"
repo_root="$(git rev-parse --show-toplevel)"
policy_file="${repo_root}/.gitleaksignore"
temp_root="$(mktemp -d "${TMPDIR:-/tmp}/empulse-gitleaks-policy.XXXXXX")"

if [[ "${gitleaks_bin}" != /* ]]; then
    gitleaks_bin="$(command -v "${gitleaks_bin}")"
fi

cleanup() {
    rm -rf -- "${temp_root}"
}
trap cleanup EXIT

actual_version="$("${gitleaks_bin}" version | tr -d '[:space:]')"
if [[ "${actual_version}" != "${expected_version}" ]]; then
    echo "Expected Gitleaks ${expected_version}, got ${actual_version}." >&2
    exit 1
fi

expected_fingerprints="${temp_root}/expected-fingerprints"
printf '%s\n' \
    '16736896dc5c18ac999c33166f3deea9cb3e8fe2:attached_assets/Pasted-STACK-CONTEXT-FLARE-SHARE-Share-with-Flare-Docs-STACK-C_1764950893439.txt:generic-api-key:378' \
    '16736896dc5c18ac999c33166f3deea9cb3e8fe2:attached_assets/Pasted-STACK-CONTEXT-FLARE-SHARE-Share-with-Flare-Docs-STACK-C_1764950893439.txt:generic-api-key:438' \
    '3829974caded6854ba5b90df57fa3a3792332be8:app/Http/Controllers/ContuctUsController.php:sendinblue-api-token:19' \
    > "${expected_fingerprints}"

if ! cmp -s "${expected_fingerprints}" "${policy_file}"; then
    echo ".gitleaksignore must contain exactly the three owner-approved fingerprints." >&2
    exit 1
fi

baseline_repo="${temp_root}/baseline-repository"
baseline_report="${temp_root}/baseline.json"
git clone --quiet --no-local "${repo_root}" "${baseline_repo}"
rm -f -- "${baseline_repo}/.gitleaksignore"

set +e
(
    cd "${temp_root}"
    "${gitleaks_bin}" git "${baseline_repo}" \
        --redact \
        --log-opts="--all" \
        --report-format=json \
        --report-path="${baseline_report}" \
        --no-banner \
        --no-color \
        --log-level=error
)
baseline_status=$?
set -e

if [[ "${baseline_status}" -ne 1 ]]; then
    echo "Expected the unignored history baseline to fail with exit code 1; got ${baseline_status}." >&2
    exit 1
fi

python3 -c '
import json
import sys

with open(sys.argv[1], encoding="utf-8") as report:
    findings = json.load(report)
with open(sys.argv[2], encoding="utf-8") as policy:
    expected = sorted(line.rstrip("\n") for line in policy if line.strip())
actual = sorted(finding.get("Fingerprint", "") for finding in findings)
if len(findings) != 3 or actual != expected:
    raise SystemExit("Unignored history findings do not exactly match the approved fingerprints.")
' "${baseline_report}" "${expected_fingerprints}"

allowed_report="${temp_root}/allowed.json"
(
    cd "${temp_root}"
    "${gitleaks_bin}" git "${repo_root}" \
        --redact \
        --log-opts="--all" \
        --gitleaks-ignore-path="${policy_file}" \
        --report-format=json \
        --report-path="${allowed_report}" \
        --no-banner \
        --no-color \
        --log-level=error
)

python3 -c '
import json
import sys

with open(sys.argv[1], encoding="utf-8") as report:
    findings = json.load(report)
if findings:
    raise SystemExit("The approved full-history scan still contains findings.")
' "${allowed_report}"

negative_repo="${temp_root}/negative-control"
git init --quiet "${negative_repo}"
git -C "${negative_repo}" config user.name "Empulse CI"
git -C "${negative_repo}" config user.email "ci@example.invalid"
cp "${policy_file}" "${negative_repo}/.gitleaksignore"
synthetic_token="$(openssl rand -hex 32)"
printf 'api_key = "%s"\n' "${synthetic_token}" > "${negative_repo}/unrecognized-secret.txt"
git -C "${negative_repo}" add .gitleaksignore unrecognized-secret.txt
git -C "${negative_repo}" commit --quiet -m "Add synthetic negative control"

negative_report="${temp_root}/negative-control.json"
set +e
(
    cd "${temp_root}"
    "${gitleaks_bin}" git "${negative_repo}" \
        --redact \
        --log-opts="--all" \
        --gitleaks-ignore-path="${negative_repo}/.gitleaksignore" \
        --report-format=json \
        --report-path="${negative_report}" \
        --no-banner \
        --no-color \
        --log-level=error
)
negative_status=$?
set -e

if [[ "${negative_status}" -ne 1 ]]; then
    echo "Expected the unrecognized synthetic finding to fail with exit code 1; got ${negative_status}." >&2
    exit 1
fi

python3 -c '
import json
import sys

with open(sys.argv[1], encoding="utf-8") as report:
    findings = json.load(report)
with open(sys.argv[2], encoding="utf-8") as policy:
    approved = {line.rstrip("\n") for line in policy if line.strip()}
if not findings:
    raise SystemExit("The negative control did not produce a finding.")
if any(finding.get("Fingerprint", "") in approved for finding in findings):
    raise SystemExit("The negative control unexpectedly matched an approved fingerprint.")
' "${negative_report}" "${expected_fingerprints}"

echo "Gitleaks ${actual_version} policy passed: exactly three historical fingerprints ignored; unrecognized finding rejected."
