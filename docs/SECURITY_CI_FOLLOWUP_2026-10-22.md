# Security fixes: main push and GitHub CI follow-up

Date: October 22, 2026, America/New_York. This is Git/CI evidence, not a deployment or launch approval.

## First pushed candidate

Main was fast-forwarded from `201da05f94109ad59818b64bd9a436121d736cb3` to `067045cc7ebed0e30cbc270373873cfc1948f13f`, preserving history. The source remediation commit is `17d2988a31e2c878602b99105c5d778f388e7ce4`; the following commit includes the reviewed Vite host allowlist change. No ignored private evidence or generated build output was staged.

[GitHub run 39893697807](https://github.com/gurulost/empulse/actions/runs/39893697807) completed:

| Job | Result | Evidence |
| --- | --- | --- |
| `test` | Passed | Actual PHP 8.2.35, PostgreSQL 16.15; 225 backend tests/1,343 assertions, twelve JS tests, build, formatting/static/cache/dependency gates, all 17 real Chrome browser journeys. |
| `secret-scan` | Passed | Strict proposed-change scan plus full-history policy: exactly three approved fingerprints, new unrecognized synthetic finding rejected. |
| `container-scan` | Failed, correctly blocking | Both actual images built; Trivy 0.76.1 found high/critical Debian package matches. Reports and SBOMs retained as the run's `container-security-evidence` artifact. No scanner bypass added. |

The actual first runtime image has Debian 13.7 and 127 high/critical package/CVE matches. The frontend image has Debian 12.15 and 57. No fixed version is listed for those matches in the reports. Counts represent package/CVE matches, not that many remotely exploitable application flaws.

## In-scope follow-up remediation

The runtime image unnecessarily inherited build-only tools, development libraries and kernel headers from the PHP extension build stage. A separate `php-runtime` stage now retains the linked runtime libraries and removes extension compilers, development packages, git and unzip. The builder stages retain their required tools. Image construction explicitly fails if a compiler or `linux-libc-dev` remains or a required PHP extension is unavailable.

CI additionally invokes the actual image's PHP security-floor check, Composer autoloader and required extensions, and Apache's configuration syntax check before scanning. These checks must pass in GitHub; source inspection alone does not qualify a changed image.

The full strict scan remains enabled for both frontend and runtime images. No `--ignore-unfixed`, severity reduction, blanket suppression, new secret ignore or accepted-risk record was added.

Run [39894557555](https://github.com/gurulost/empulse/actions/runs/39894557555) on `96064f2` caught a cleanup defect before runtime publication: the inherited concrete `libc6-dev`/`linux-libc-dev` packages were not removed by purging the virtual `libc-dev` build dependency alone. The compiler was gone but the explicit no-kernel-headers assertion correctly failed. The fix names the concrete build-only packages in the purge list; the assertion remains intact. Another clean image build and scan is required.

Run [39894896681](https://github.com/gurulost/empulse/actions/runs/39894896681) also exposed a flaky synthetic negative control: one randomly generated hex value did not trigger Gitleaks' generic-key heuristic. This is not evidence of an additional exposed credential. The fixture now derives a deterministic, never-issued URL-safe value from a public test label, avoiding random entropy variation. Both the exact-three baseline assertions and the requirement that a new finding fail remain unchanged; local verification uses CI-pinned Gitleaks 8.24.3.

### Qualified runtime cleanup, unresolved scan

That run, on `9b28e689032651994a9eb5d587f32d596c0c004b`, successfully built the cleaned runtime image and passed its actual PHP 8.2.35 guard, autoloader/required-extension smoke, and Apache `Syntax OK` check. The application and browser job passed. The scanner artifacts establish:

| Image | High | Critical | Total | Listed fixed versions |
| --- | --- | --- | --- | --- |
| Runtime after cleanup | 58 | 1 | 59 | None |
| Frontend toolchain | 56 | 1 | 57 | None |

The runtime reduction is 127 to 59 package/CVE matches; `linux-libc-dev` is absent. Installed Composer-vendor and Node-package scan sections have zero reported vulnerabilities. Retained Debian native libraries remain affected or require individual vendor/reachability disposition; this is **not a clean-image result**. The next commit changes only the synthetic secret fixture and evidence documents, not these image definitions. Its full GitHub CI must still run; no scan job is allowed to fail silently.

## Remaining platform decision

The first image's libxml2 is `2.12.7+dfsg+really2.9.14-2.1+deb13u3`. Debian still lists the relevant stable package as vulnerable for [CVE-2026-6653](https://security-tracker.debian.org/tracker/CVE-2026-6653); its assessment is minor/no-DSA, while the scanner imports a critical rating from another source. Preserve that distinction. Vendor-confirmed non-applicability for [zlib's unbuilt contrib MiniZip code](https://security-tracker.debian.org/tracker/CVE-2023-45853) also cannot justify suppressing unrelated findings.

Removing headers/tools does not repair affected retained libraries. If strict CI still fails after the cleanup, the release owner must choose a separately qualified supported runtime/base strategy or explicitly commission per-finding applicability/mitigation review. Do not silently accept risk, import Debian unstable libraries into the stable image, or switch the web-serving architecture merely to obtain a green badge. The official Node image documentation also identifies Alpine/musl platform-support differences; a smaller base is not automatically a compatibility-qualified replacement. [Official Node image guidance](https://github.com/nodejs/docker-node/blob/main/README.md).

The [audit's credential/device/provider gates](SECURITY_AUDIT_2026-10-22.md) remain open and separate from this requested push/CI task. The Brevo history decision remains the sole existing accepted risk: the revoked credential is still visible in old commits, no history rewrite occurred, and only its three exact fingerprints are ignored. Empulse has not been deployed by this work.
