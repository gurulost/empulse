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

Run [39895455740](https://github.com/gurulost/empulse/actions/runs/39895455740), on `1db40a9966e2b45278be77df887fb441049ecba6`, verifies the deterministic secret-policy job and the same successful image builds/smokes. Container counts remain 59 runtime/57 frontend, with no listed fixes. The application job passed backend/build/static gates but stalled for over five minutes at Chrome installation; cancellation was requested to obtain the runner logs, not to waive browser testing. That run does not establish a final browser pass. CI now bounds browser installation to five minutes and the application job to twenty minutes; a fresh run is required. The initial installer stall's cause remains unverified while logs are unavailable.

## Remaining platform decision

The first image's libxml2 is `2.12.7+dfsg+really2.9.14-2.1+deb13u3`. Debian still lists the relevant stable package as vulnerable for [CVE-2026-6653](https://security-tracker.debian.org/tracker/CVE-2026-6653); its assessment is minor/no-DSA, while the scanner imports a critical rating from another source. Preserve that distinction. Vendor-confirmed non-applicability for [zlib's unbuilt contrib MiniZip code](https://security-tracker.debian.org/tracker/CVE-2023-45853) also cannot justify suppressing unrelated findings.

Removing headers/tools does not repair affected retained libraries. If strict CI still fails after the cleanup, the release owner must choose a separately qualified supported runtime/base strategy or explicitly commission per-finding applicability/mitigation review. Do not silently accept risk, import Debian unstable libraries into the stable image, or switch the web-serving architecture merely to obtain a green badge. The official Node image documentation also identifies Alpine/musl platform-support differences; a smaller base is not automatically a compatibility-qualified replacement. [Official Node image guidance](https://github.com/nodejs/docker-node/blob/main/README.md).

The [audit's credential/device/provider gates](SECURITY_AUDIT_2026-10-22.md) remain open and separate from this requested push/CI task. The Brevo history decision remains the sole existing accepted risk: the revoked credential is still visible in old commits, no history rewrite occurred, and only its three exact fingerprints are ignored. Empulse has not been deployed by this work.

## Owner-authorized replacement qualification

The owner explicitly authorized broader container/base and web-runtime remediation after the preceding scans, without authorizing deployment, paid services, credential rotation or global computer changes. The original Debian results above remain historical evidence, not an accepted-risk exemption for the new stack.

The candidate uses digest-pinned official PHP 8.5.12 FPM and Node 22.23.4 Alpine 3.24 bases. Preliminary `linux/amd64` Trivy 0.76.1 scan of the PHP base reports no high/critical findings. The Node Alpine OS section is clean; its old bundled npm dependencies still match advisories and are removed/replaced with the verified isolated npm 11.21.1 as before. This base-only evidence is not a final-image clearance. [PHP image](https://hub.docker.com/_/php), [Node platform/support tradeoffs](https://github.com/nodejs/docker-node/blob/main/README.md).

The replacement runtime runs nginx/FPM as `www-data`, exposes internal HTTP 8080 only, and confines FPM to loopback 9000. Tini plus the guarded entrypoint handles graceful termination and failure of either child. nginx executes only the fixed front controller and blocks other PHP/dotfile requests. Access logs omit bearer URI/query/header/address data; unredactable nginx request-error context is not retained, while startup/process diagnostics, Laravel-redacted errors and status/timing metrics remain. The non-root PostgreSQL 16.15 image removes the unused gosu binary rather than pretending its bundled Go version was updated. [FastCGI guidance](https://nginx.org/en/docs/http/ngx_http_fastcgi_module.html), [Tini signal/reaping model](https://github.com/krallin/tini).

CI must build/scan runtime, frontend-toolchain, PHP-vendor-builder and PostgreSQL images, exercise guarded production startup on an internal no-outbound-network synthetic PostgreSQL fixture, verify non-root identities, block sensitive routes/log canaries, and test child-failure propagation and graceful shutdown. The vendor stage is exported from the immediately preceding clean no-cache build, not an external cache. The application job also builds the real image and runs browser journeys through nginx/FPM plus a containerized worker, with synthetic test configuration. No runtime guard is disabled for production; test commands are overridden explicitly, and the default guarded entrypoint has a separate smoke test. Full qualification is pending at this source checkpoint; no suppressions were introduced.

The prior Chrome installer logs became available after cancellation: an apt package transfer took 4 minutes 15 seconds for 22 MB. That supports a slow external download, not an application defect or proof of an indefinitely hung process. A fresh bounded run on `7ebe156` passed the application and deterministic secret jobs; its old Debian container scan remained red. The replacement workflow allows ten minutes for browser installation and twenty for the application job, retaining meaningful bounds without treating a slower package mirror as a failed product test.

The first replacement run exposed an ambient-configuration assumption in the liveness test: newly supplied CI release/environment identity adds valid response fields. The failure was reproduced locally. The test now explicitly selects absent identity and an unavailable database, retaining exact JSON assertions while genuinely testing independence from backing services; the separate configured-identity test remains intact. Containerized journeys also use database-backed sessions/cache, not private per-container file stores, so web and worker share production-like state.

The first rootless PostgreSQL fixture successfully initialized as the non-root user but its probe raced the temporary Unix-socket initialization server. The probe now requires a successful authenticated TCP `select 1` against the final named database, rather than interpreting `pg_isready` as proof database creation is complete. nginx startup/config checks also explicitly send pre-configuration errors to stderr (`-e stderr`), avoiding its package-default root-owned log path. Neither correction disables a runtime or scan assertion.

The next fixture exposed Docker's internal-network port-publication restriction, not an HTTP application error. Default-entrypoint smoke requests now execute over HTTP inside the web container, preserving the no-outbound-network fixture; the separate browser job still verifies host-to-container ingress. Actual-image scans run after a successful image build even when a runtime/trust smoke fails, retaining independent vulnerability evidence while the job still fails closed on every failed step.

Run `39898986973` built all four replacement images and their strict scans reported zero high/critical findings, with no new ignores. Actual PHP trust bundle/directory inspection passed with 118 unique certificates; Node's built-in store passed with 116. Guarded rootless startup, release identity, dotfile/PHP routing and log-canary checks passed. Its lifecycle check caught a real supervision defect: BusyBox 1.37 `wait -n` with explicit PIDs takes its sequential-wait path, leaving FPM alive after nginx exits. This was confirmed by reading `shell/ash.c:waitcmd` in the [official 1.37.0 release source](https://busybox.net/downloads/busybox-1.37.0.tar.bz2). The entrypoint now uses the no-operand form for its only two background jobs; source regression coverage rejects the old form, and CI must fault each child separately and prove graceful stop. No lifecycle timeout or assertion was relaxed. CLI floor diagnostics go to stderr so machine-readable command output remains usable.

The next run proved both individual child-failure paths but caught a separate graceful-stop defect: the official FPM base's inherited `STOPSIGNAL SIGQUIT` was incompatible with a supervisor trapping SIGTERM/INT, so Docker eventually force-killed the container. The final image now explicitly selects `STOPSIGNAL SIGTERM`; the supervisor converts it into graceful SIGQUIT for nginx/FPM. Source and actual-image assertions pin this contract, and the unchanged fifteen-second graceful-stop test must still pass. This also gives separately executed worker commands the expected Laravel SIGTERM shutdown signal.
