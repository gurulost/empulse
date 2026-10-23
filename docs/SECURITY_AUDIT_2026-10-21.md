# Empulse security audit — October 21, 2026

Historical checkpoint: [the October 22 refresh](SECURITY_AUDIT_2026-10-22.md) supersedes current host/browser facts and expands the incident baseline. The original temporary evidence directory was no longer present at that refresh; the follow-up records what was recaptured rather than claiming old raw artifacts remain available.

Status: local remediation completed; host, final-image, current-revision CI, and deployment assurance remain open. This is not production approval or a guarantee of safety.

Incident reference: 2026-10-21 America/New_York. Research and execution began that evening; UTC timestamps fall on October 22. Verification checkpoint: 2026-10-22 03:51 UTC (October 21 23:51 EDT); public status snapshots retain their own timestamps below. Source checkout: `201da05f94109ad59818b64bd9a436121d736cb3`; isolated branch: `codex/security-audit-2026-10-21`. No pre-existing tracked edits were present. No Git history was rewritten, account settings changed, credentials rotated, services stopped, or deployment made. Changes remain local for review.

## Urgent findings and next actions

1. **Patch the actual Mac PHP service before exposing it.** CLI and the running loopback PHP-FPM executable are 8.4.14, below the October critical fixes. Installing a different binary without replacing the serving process is insufficient. No public exposure or exploitation was demonstrated. If any tunnel/proxy exposes this service, isolate that route until patched. Owner: device/service administrator.
2. **Review the possible OpenAI credential in a legacy ignored cache.** A 2023 Python voice-assistant script is stored in an old spreadsheet cache, with a key-shaped literal at line 109. It was not executed, tested against the provider, or added to Git. Ownership, permissions, validity, and other consumers are unknown. Preserve evidence; if real/live, the issuing account owner must revoke it and review usage from a trusted session. This is not the accepted Brevo history exception.
3. **Do not approve the final container from these pins alone.** A read-only Trivy scan of the official PHP base reports unresolved distribution/native findings. Several are conditional or header-package matches; libxml2 has confirmed Debian vulnerable-package entries. The final application image has not been built or scanned. Owner: release/security reviewer.
4. Merge only after current-revision CI on patched PHP/PostgreSQL 16 and updated Chromium passes. The historical green July CI run is not evidence for this branch. No verified deployed runtime exists.

## Scope, evidence, and compromise precheck

The product is a Laravel/Vue organizational diagnostic and continuous-listening application: company/roster setup, governed surveys, employee response capture, privacy-gated aggregate analytics, leadership action/follow-up, and Stripe subscriptions. Employee confidentiality, company isolation, and billing integrity are consequential security boundaries. Product direction was read from the canonical vision, architecture, and README, not inferred from legacy code.

Before package installs, tests, or builds, inspection covered manifests/locks, installed-package metadata, lifecycle scripts, repository history, common local Python/package caches, filenames/startup hooks, and the vendor's LiteLLM indicators. No project Python environment or LiteLLM dependency was found. No inspected location contained the reported `cook.pth`, `cook.py`, `remote_code_loader.lock`, or matching loader/domain indicators. Homebrew's ordinary `sitecustomize.py` and the inspected pipx path hook were read rather than executed to assess their content. Reviewed JavaScript lifecycle scripts were ordinary native-addon setup; dependency installation disabled hooks. Subsequent Python JSON checks use `python3 -I -S` to skip startup hooks.

These checks found **no evidence of the named LiteLLM compromise in the inspected project/tool locations**. They do not establish historical or whole-device freedom from compromise: deleted artifacts, old CI caches, remote build layers, unrelated environments, persistence, and provider access logs were not exhaustively available. A current clean lockfile/source tag cannot prove old wheels or floating images were clean.

A wider secret scan subsequently identified the ignored legacy Python cache described above. Static inspection shows a desktop voice-assistant program with microphone/screenshot/file-launch features and an OpenAI request function, not an Empulse feature or proof of the named malware. Its unexplained provenance and possible credential still need owner assessment. The source file was left untouched; a mode-0600 evidence copy and SHA-256 were preserved in a mode-0700 temporary audit folder. File SHA-256: `9cb935838bc7dcd37d1e8a68d428b56fac786bedc5a63d6601657d4562352e1d`. Do not publish the file or its literal key.

## Asset and delivery inventory

| Asset | Observed evidence | Coverage/status |
| --- | --- | --- |
| Repository | Public `gurulost/empulse`; remote main at source SHA above; last pushed July 28 | Source/history inspected; no push in this audit |
| Backend | Laravel 12.64.0 initially; PHP 8.4.14 CLI; Composer 2.9.1 | Locked dependencies patched locally; CLI remains old |
| Local PHP service | PHP-FPM 8.4.14 executable, loopback port 9000 | Affected; no service restart/host modification authorized |
| Frontend/build | Vue 3.5.24, Axios 1.18.1, Vite 8.1.5, Vitest 4.1.10 initially; local Node 22.23.4/npm 11.6.2 | Packages updated below; Node's applicable October fixes already present locally |
| Browser automation | Playwright 1.58.2 initially (Chromium 145 generation) | Package now 1.64.1; fresh browser download/real journeys pending patched CI |
| Database | Local running PostgreSQL 14.21, loopback 5432; separate 16.15 installed | No local app `.env`/DB connection to establish use; tests this audit use disposable SQLite. Running PG14 needs owner patch/restart planning |
| Container contract | Dockerfile + Compose; PHP/Node/Postgres previously floating tags | New digest pins inspected via official registry metadata and scanned without execution; Docker daemon unavailable, final build unverified |
| CI | GitHub-hosted `ubuntu-latest`, no self-hosted/GitLab runner declared | July 28 run `30393757357` green on old SHA. Current branch not submitted. Runner/action embedded runtimes not established by local Node version |
| GitHub controls | Read-only default workflow token; all Actions allowed; SHA pinning not account-enforced; main protection endpoint reports unprotected and rulesets list empty | Workflow least privilege/pins improved locally; account governance unchanged |
| Replit development | Matching `empulse` project metadata last updated November 19, 2025; exact project's publish-status lookup returned no publication | No runtime/log/secret inspection; old history messages mention publication, so other copies/previews are not ruled out |
| Production/staging/edge | No entries from GitHub Deployments API; no chosen/provider-verified environment in canonical docs | Unverified, not proof of no deployment anywhere. Need exact hostname, provider/service identity, release/digest, ingress and logs |
| Railway | No repo deployment/config/reference found; user believes unrelated and prohibited connector use | No further account inspection after instruction. One read-only listing had already been attempted before that instruction. Public incident pages only thereafter; no Railway-specific rotation assumed necessary for Empulse |
| Storage/jobs | Local/public avatar disk; encrypted database-backed roster staging; web + worker + scheduler contract | Shared database/key, public avatar disk, supervision and provider retention configuration unverified; see deployment checklist |
| Integrations | Stripe/Cashier, Brevo, Socialite/OAuth, reCAPTCHA; no project AI agent/MCP execution pipeline | Provider configuration, scopes, access/usage logs, and callbacks not available; no secret values retrieved |
| Device/tool trust | macOS 26.5.2 (25F84); Chrome 155.0.8059.85; OpenSSL 3.6.4, curl 8.17.0, SQLite 3.51.0, libpq 18.1 observed locally | Package versions are inventory, not proof all native advisories/backports are resolved; OS/browser and embedded runtimes need owner review |

Resolved/installed inventories and redacted scan outputs are retained outside Git in the private temporary audit evidence directory. Retain them in an approved private evidence store before temporary-folder cleanup. No private source was uploaded to a scanning service; package names/versions were queried against public advisory services and official public image layers downloaded for local scanning.

## Mandatory incident applicability matrix

Each status is bounded to the named asset. No row is “verified remediated in production.” Research was refreshed during the audit, including the surrounding week's advisories.

| Baseline | Status and evidence | Next check / source |
| --- | --- | --- |
| A PHP 109618 / 109619 | **Affected:** running local 8.4.14. **Remediated locally, deployment pending:** image/CI/entrypoint floors now 8.2.35 / 8.3.36 / 8.4.27 / 8.5.12. Web-SAPI RCE precedes application authorization; PHP package audit cannot detect it. | Vendor release October 21 at 13:30 UTC following October 20 notice. Patch serving SAPIs/embedded PHP and restart all workers; isolate public routes if patch cannot be applied. [PHP](https://www.php.net/), [CERT-EU](https://cert.europa.eu/publications/security-advisories/2026-018) |
| B Node ten issues | Local 22.23.4 already includes applicable fixes. **Remediated locally, deployment pending:** exact build/CI pin replaces floating 22 and Replit Node 20. Embedded/action/provider Node remains **unverified**. | Inventory actual build processes, not just package engines; maintained fixed releases 22.23.4 / 24.22.1 / 26.12.1. [October release](https://nodejs.org/en/blog/vulnerability/october-2026-security-releases), [support lines](https://nodejs.org/en/about/previous-releases) |
| C Railway | **Not applicable to declared repo delivery**, no integration found; account-wide historical use **unverified** by user direction. No evidence tying this cache key or Empulse to Railway. | Disclosure recommends revocation of stored env/private-registry credentials and log review for affected accounts. Public status at October 22 03:32 UTC says Enterprise builds available, other plans staged, not universal recovery. Recheck only if account owner identifies relevant historical use. [Disclosure](https://blog.railway.com/p/security-disclosure-wednesday-october-21st), [status](https://status.railway.com/) |
| D LiteLLM | **Not applicable to current project dependency graph**; historical remote/cached execution **unverified**. No installed artifact or inspected IOC match found. | Vendor malicious 1.107.0 wheels: October 21 11:39:51–13:23 UTC; image publication windows extend to 14:20 UTC for Docker Hub and 13:51 UTC for GHCR. Vendor recommends clean 1.106.2. Match original wheel hashes/image digests, not tags; see IR checklist. [Vendor](https://docs.litellm.ai/blog/security-incident-oct-21-2026), [original research](https://www.stepsecurity.io/blog/litellm-1-107-0-compromised-pypi-backdoored-wheels-credential-stealer) |
| E eMudhra/emSign | Local Node bundle and active PHP Homebrew CA file inspected: no affected G3/C1/C3 roots, safe G1 retained. **Unverified** system dynamic distrust, CI/action/container final bundles, app-bundled Node/Java/Rust. System PEM still contains C1/C3. | Current common pip 26.2.2/certifi 2026.10.19 are patched, but older separate tool envs exist (e.g. pip 24.x/certifi 2026.2.25). uv not found active; don't infer other embedded stores fixed. Verify pip >=26.2.2, uv >=0.13.4, certifi >=2026.10.19 independently. Never disable TLS verification. [Python guidance](https://discuss.python.org/t/information-for-python-users-regarding-emudhra-emsign-ca-certificate-security-incident/109576), [uv](https://github.com/astral-sh/uv/security/advisories/GHSA-hq85-5cpj-mr62), [certifi](https://github.com/certifi/python-certifi/security/advisories/GHSA-6q48-6c7w-9gw2) |
| F GitLab full October release | **Not applicable to declared GitHub-hosted workflow**; self-managed supporting infrastructure **unverified**. All twelve release issues considered, including regex RCE CVE-2026-110264, not just GitLab.com status. | Infra owner must inventory any GitLab/runners outside repo; fixes 19.5.1 / 19.4.3 / 19.3.6 / 19.2.9 / 19.1.9 / 19.0.10 / 18.11.13, following vendor upgrade path. [Full bulletin](https://docs.gitlab.com/releases/patches/patch-release-gitlab-19-5-1-released/) |
| G Java/GraalVM | **Not applicable to project manifests/images/workflow**; no local standalone JDK found in standard location. Embedded vendor runtimes remain **unverified**. | Identify distribution first; Oracle CVE-2026-91403 alert October 21 is separate from October 20 CPU. Do not apply Oracle version numbers to all OpenJDK builds. [Alert](https://www.oracle.com/security-alerts/alert-cve-2026-91403.html), [CPU](https://www.oracle.com/security-alerts/cpuoct2026.html) |
| H BIND/Unbound | **Not applicable to declared app services**; no named/unbound process found. Router, upstream recursive/authoritative DNS are **unverified**. | BIND 19630/94300/96888/97313: maintained 9.20 branch fix 9.20.30; use actual vendor/branch matrix. Unbound 1.26.2 fixes 97206/50106/96405/109125/35501. Obtain DNS appliance versions/provider attestation. [ISC](https://kb.isc.org/docs/aa-00913), [NLnet Labs](https://nlnetlabs.nl/projects/unbound/download/) |
| I Cisco/Splunk | **Not applicable to declared app services/dependencies**; network and security appliances **unverified**. Complete Cisco October 21 set reviewed (Snort 3, Expressway, UC, IOS XR, Secure Client); separate SOAR 1010/1011/1012 reviewed. | Network owner inventory/model/version comparison against complete bulletin. SOAR advisories point to 8.7.0; don't reduce 1012's third-party set to one CVE. Smart Install legacy hardening is separate. [Cisco set](https://sec.cloudapps.cisco.com/security/center/content/CiscoSecurityAdvisory/cisco-sa-notice-QGwHnJ6k), [1010](https://advisory.splunk.com/advisories/SVD-2026-1010), [1011](https://advisory.splunk.com/advisories/SVD-2026-1011), [1012](https://advisory.splunk.com/advisories/SVD-2026-1012) |
| J Header Accordions/QPACK | **Not applicable to declared Apache HTTP/1 origin**; actual edge HTTP/3 termination **unverified**. Local ngtcp2/nghttp3 presence is not proof of this flaw. | October 20 original report: quic-go >=0.57.0, quiche >=0.29.3, lsquic >=4.9.0; Wireshark 4.6.2/4.4.12. Other named consumers require current vendor disposition; ngtcp2/nghttp3, IIS, Envoy were not affected in report. Inventory edge/provider first. [Original report](https://foxio.io/blog/header-accordions-qpack-amplification-attacks-against-http-3-servers) |
| K wider stack | **Affected / remediated locally / unverified** by asset: PHP/npm packages patched below; PostgreSQL 14.21 old; final-image native findings open; old local Chrome; action/bundled runtimes unknown. No .NET or Signal/RingRTC dependency in app. | Check provider OS/kernel, container distro backports, browser/Electron, MCP/agent vendor updates. No unsupported blanket Bluetooth advice. [PostgreSQL](https://www.postgresql.org/support/security/), [Apple](https://support.apple.com/en-us/100100), [Chrome](https://chromereleases.googleblog.com/), [Mozilla](https://www.mozilla.org/en-US/security/advisories/), [.NET advisory](https://access.redhat.com/errata/RHSA-2026:86594), [Signal record](https://nvd.nist.gov/vuln/detail/CVE-2026-113380) |

### All ten Node issues

From the October 21 Node advisory; local 22.23.4 includes the seven applicable fixes. New pins cover build/CI; provider and embedded copies require independent proof.

| CVE suffix (2026) | Issue / severity | Affected maintained lines |
| --- | --- | --- |
| 105482 | DNS NUL truncation / high | 22, 24, 26 |
| 105483 | Fullwidth IP TLS bypass / high | 24, 26 (not Node 22) |
| 106069 | Loosely parsed IP TLS bypass / high | 22, 24, 26 |
| 111497 | Brotli out-of-bounds write/RCE / high | 22, 24, 26 |
| 105481 | Keep-alive response confusion / medium | 22, 24, 26 |
| 106072 | Noncanonical IPv4 SAN validation / medium | 22, 24, 26 |
| 106074 | Snapshot entropy reuse / medium | 22, 24, 26 |
| 106070 | ZIP prefix validation / low | 26 only |
| 106071 | Permission wildcard lookup / low | 26 only |
| 106073 | realpathSync permission bypass / low | 22, 24, 26 |

Node is a build/dev/test dependency here, not the declared production HTTP server. This limits direct request exposure but does not make a vulnerable build process acceptable. The Node release also updates bundled trust and native/network components.

### Advisory cross-checks and date boundaries

Composer/Packagist and npm audit covered all lockfile dependencies, including development/transitive/platform packages, not only production roots. Exact-version OSV queries independently confirmed Laravel/Axios/Vite advisories; OSV returned no entries for the queried Symfony/Vue versions despite other feeds' findings. That discrepancy is not a clearance. Symfony's maintainer publication is October 15; Packagist's `reportedAt` differed, so it is not treated as the incident date. The selected Symfony 7.4.21 also includes the follow-up separator regression repair.

CISA's public KEV JSON was retrieved after the browser endpoint failed: catalog `2026.10.21`, snapshot `2026-10-21T14:04:39.8983Z`. Queried baseline IDs and Symfony 108082 were absent in that snapshot. This predates some releases, is not a complete exploitation assessment, and cannot lower severity. No KEV-addition date is asserted for absent entries. Mozilla's specific eMudhra blog returned 403; Python/certifi/uv guidance and Mozilla's advisory index were used instead. Debian tracker requests intermittently failed; successful exact libxml2 entries were used rather than upstream-only comparisons.

Additional supply-chain check: the original [Datadog npm-worm report](https://securitylabs.datadoghq.com/articles/npm-worm-compromises-popular-npm-packages/) was compared with current package identities (e.g. installed keyv 4.5.4, not malicious 6.0.0). No reported malicious installed-package indicator was found; historical deleted caches remain outside that conclusion.

## Prioritized findings register

Severity indicates potential impact and prerequisites, not demonstrated exploitation. Owner roles below are requested assignments, not evidence someone has accepted them.

| ID / priority | Asset, exact evidence and exposure | Remediation / owner / status |
| --- | --- | --- |
| SEC-01 Critical if publicly exposed | PHP-FPM/CLI 8.4.14; CVE-2026-109618/109619. Loopback listener observed, public proxy exposure not established. Framework authorization cannot prevent engine-level parsing flaws. | Device administrator: patch >=8.4.27 or another fixed branch, inspect actual executable, restart dependent processes deliberately. **Affected; host change pending.** [CERT-EU](https://cert.europa.eu/publications/security-advisories/2026-018) |
| SEC-02 High | Inactive authenticated employee and inactive admin requests returned 200 before fix. Login-time status check did not revoke existing access; explicit Laravel 12 alias was missing. | Per-request active-account enforcement and session invalidation; regression now denies access. Engineer: **remediated locally, deployment pending**. `Authenticate.php`, `bootstrap/app.php`, `SecurityAuditRegressionTest.php`. |
| SEC-03 High, misconfiguration prerequisite | With signing secret absent, an unsigned `/stripe/webhook` returned 200 and persisted an event. Cashier conditionally omitted signature middleware. Public billing/entitlement processing made fail-open behavior consequential; no external forged payment was attempted. | Verify raw body/signature before persistence regardless of middleware; missing key 503, invalid signature 403; valid signed event/replay tests pass. Engineer: **remediated locally, deployment pending**. `StripeWebhookController.php`, `BillingFlowTest.php`. |
| SEC-04 High | Symfony HTTP Foundation 7.4.14; CVE-2026-108082 request-header ReDoS reaches request construction without authentication. | Lock/install 7.4.21; release owner rebuilds all web/worker artifacts. **Remediated locally, deployment pending.** [Maintainer](https://symfony.com/blog/cve-2026-108082-redos-in-headerutils-split-reachable-through-the-cache-control-request-header) |
| SEC-05 High potential credential exposure | Ignored `storage/framework/cache/laravel-excel/laravel-excel-9dJsO9PPl1LFTvCHVCs4RqKNZV77OLJP:109`; 51-character OpenAI-shaped literal in old Python script, mtime September 7, 2023. No Git history entry for file; not an app AI integration. | Account owner identify key securely, revoke if genuine/live, review use/billing/permissions/other consumers. No provider validation or revocation performed; **unverified validity/exposure**, not accepted risk. Preserve private evidence; never broaden Gitleaks exceptions. |
| SEC-06 High/conditional | Five PHP package groups and fifteen npm package groups initially had advisories; detailed version changes below. Includes CommonMark HTML/extension denial-of-service/XSS and build-tool attack surfaces. Server-side Axios/SSR capabilities not automatically reachable in this browser-bundled usage. | Targeted compatible upgrades, hooks disabled, no forced major audit fix. **Remediated locally, deployment pending**; scans now zero. Composer/npm reports contain advisory IDs and ranges. |
| SEC-07 Medium configuration | Vite accepted all hosts and bound all interfaces, exposing development source on reachable dev hosts/DNS-rebinding paths. | Loopback and explicit host list defaults, regression coverage. Named remote preview host must be configured explicitly. **Remediated locally; remote workspace update pending.** [Vite guidance](https://vite.dev/config/server-options.html#server-allowedhosts) |
| SEC-08 Medium configuration | Floating runtime/action refs; Docker context allowed ignored runtime storage/caches/private uploads. Compose avatar volume did not match current Laravel disk. | Exact digests/SHAs, read-only CI token, ignore private/generated runtime state, correct avatar volume and loopback origin. **Remediated locally; image/ingress/storage verification pending.** Existing volume migration requires review. |
| SEC-09 Release blocker / distribution findings | Pinned PHP base Debian 13.7: Trivy 125 high + 2 critical package/CVE matches, 86 unique high/critical IDs; 68 high/critical matches are `linux-libc-dev` headers, not proof a container runs that kernel. libxml2 `2.12.7+dfsg+really2.9.14-2.1+deb13u3` is marked vulnerable by Debian. | Security/release owner: final-image SBOM and per-package vendor/reachability triage, patched supported base or reviewed mitigation; no automatic acceptance. **Unresolved; final image not built.** [Debian 6653](https://security-tracker.debian.org/tracker/CVE-2026-6653), [86138](https://security-tracker.debian.org/tracker/CVE-2026-86138) |
| SEC-10 High, authenticated DB prerequisites | Running PostgreSQL 14.21 below current 14.24; server and client advisories include CVE-2026-16239 type confusion and 19385 pg_dump overflow. App use not proven. | Device/DB owner: back up, patch correct major and clients, planned restart; use >=16.15 for selected app environment. Never point PG16 at PG14 data directory. **Affected local service; app mapping unverified.** [PG security](https://www.postgresql.org/support/security/) |
| SEC-11 Medium governance | Public main has neither branch protection nor listed rulesets; unrestricted allowed Actions/no account SHA enforcement. New workflow pins help but cannot prevent account-approved bypass. | Repo owner configure PR/check review protections, least-privilege collaborators, SHA/action policy, MFA and audit-log review. **Unresolved account change**, not silently enabled. |
| SEC-12 Accepted historical risk | Three known Gitleaks fingerprints only; owner-confirmed revoked/deactivated Sendinblue/Brevo credential remains visible in old commits/clones. | **accepted_risk**, repository owner/mail administrator. Keep exact exception and strict new/current scans. No history rewrite; contingency only. Existing [history runbook](GIT_HISTORY_SECRET_REMEDIATION_RUNBOOK.md) and R-004 remain authoritative. |

### Container findings are not equivalent to confirmed app exploits

The PHP base scan found 1,783 package/CVE matches (1,034 distinct IDs), including 970 low, 682 medium, 125 high, 2 critical and 4 unknown. No high/critical match had a fixed version in the scanner's Debian feed at scan time. This does not imply patches cannot exist elsewhere or approve their use.

Non-header high/critical groups include util-linux 76642/78408/78409/78410 (privileged mount/namespace conditions), curl 12064/8458 (SFTP/SCP host verification or connection reuse prerequisites), ACL 54369, ncurses 2025-69720, Perl Archive-Tar 9538, systemd-homed 16742, and libxml2 6653/74860/86138/86139/86140/86142/86143/86144. The systemd libraries do not prove a homed daemon is present; libxml2 Python-binding issues do not prove those bindings run. Conversely PHP links XML libraries, so absence of a dependency-level PHP advisory is insufficient. Debian calls 6653 a minor/no-DSA issue although the scanner borrows a critical severity from another vendor. Preserve both assessments, do not flatten them to “critical app RCE” or “false positive.”

The application currently accepts CSV rather than arbitrary XML and uses fixed HTTPS provider endpoints. No proof was obtained of an attacker-controlled XML path exercising a named native flaw. That bounds reachability, not package affectedness. Do not mix Debian unstable libraries into a stable image without a supported compatibility/security plan. Final-image build, native extensions, installed package set, CA bundle and actual exposed paths remain release gates.

Other official base scans (all `linux/amd64`, not every image platform):

| Image / component | Scanner results | Disposition |
| --- | --- | --- |
| Node 22.23.4 bookworm-slim OS | 228 matches; 56 high, one critical | Critical CVE-2023-45853 is source-package MiniZip: [Debian explicitly says the vulnerable contrib code is not built into these binary packages](https://security-tracker.debian.org/tracker/CVE-2023-45853). This is vendor evidence, not a blanket suppression; other matches still require disposition. |
| Same Node image bundled npm/tool packages | 21 matches; eleven high; ten fixable high matches | Includes brace-expansion 2.0.2, ip-address 10.1.0, pacote 19.0.2/20.0.1, picomatch 4.0.3 and sigstore 3.1.0. App `npm audit` does not inspect the image's global npm tree. Upgrade the bundled CLI through a compatible, independently scanned toolchain and rescan the final build stage; no untested global Mac npm major upgrade was made. |
| PostgreSQL 16.15 alpine OS | Zero reported Alpine package findings | Not a whole-image clearance. |
| Same Postgres image `usr/local/bin/gosu` | 61 Go stdlib matches; 25 high, one critical; built with Go 1.24.6 | Version-based scanner matches do not establish reachable TLS/HTTP code in a privilege-switch helper. [The critical Go TLS advisory](https://pkg.go.dev/vuln/GO-2026-4337) requires TLS resumption; [upstream gosu entry point](https://github.com/tianon/gosu/blob/master/main.go) switches user and execs, rather than exposing a network server. Exact-binary reachability/vendor rebuild disposition remains open; no blanket Go exception. |

Patched language version, bundled package manager, Go helper, OS libraries and actual runtime exposure are separate claims. No container was started, no final image was built, and no modified Dockerfile was promoted from these base-only results.

## Repository changes and compatibility notes

| Package/tool | Before | After |
| --- | --- | --- |
| laravel/framework | 12.64.0 | 12.70.1 |
| symfony/http-foundation | 7.4.14 | 7.4.21 |
| league/commonmark | 2.8.3 | 2.10.4 |
| league/flysystem | 3.35.2 | 3.36.0 |
| phpseclib/phpseclib | 3.0.55 | 3.0.57 |
| axios | 1.18.1 | 1.20.0 |
| vue (coherent runtime/compiler family) | 3.5.24 | 3.5.44 |
| vite | 8.1.5 | 8.3.5 |
| vitest | 4.1.10 | 4.1.12 |
| @playwright/test | 1.58.2 | 1.64.1 |
| @humanfs/node | 0.16.7 | 0.16.8 |
| @parcel/watcher | 2.5.1 | 2.6.0 |
| brace-expansion | 5.0.8 | 5.0.12 |
| nanoid / source-map-js / follow-redirects | vulnerable prior lock entries | 3.3.20 / 1.2.2 / 1.16.1 |
| postcss-selector-parser | prior lock entry | 7.1.7 |

No application framework major upgrade or forced audit fix was used. Lockfiles and installed environments were rescanned after upgrades; Vitest's first candidate still had two advisories and was advanced to the patched 4.1.12 before validation.

The initial Composer scan recorded 16 advisories across five packages: Laravel CVE-2026-102279 (debug-page XSS, requiring debug output), twelve CommonMark raw-HTML/extension/XSS/complexity advisories including CVE-2026-71488/71478 (features and untrusted input determine reachability), Flysystem CVE-2026-102601 (invalid-UTF-8 path handling), phpseclib CVE-2026-84308 (X25519 timing prerequisites), and the directly reachable Symfony issue above. Updates are not presented as proof that every optional feature was exposed. Exact advisory links/ranges and npm's initial fifteen-package result are preserved in `dependency-audits-before.txt`.

Image references are exact in Dockerfile/Compose/CI: PHP 8.2.35 Apache digest `fa8966fb…224d4`, Node 22.23.4 bookworm-slim `de170b39…f38d`, PostgreSQL 16.15 alpine `deb28ee4…7815`. Full digests are in source. Composer 2.9.3's executable only is copied from a pinned official image; its old image runtime is not executed. The dependency stage now uses the patched PHP base and refreshed OS CA package. `npm ci --ignore-scripts` suppresses dependency lifecycle hooks; reviewed `npm run build` remains explicit. Native optional-addon fallback and updated Chromium require the CI/platform journey, not just a successful Mac build.

New PHP startup guard rejects old, prerelease, EOL and unreviewed future branches before Composer/Laravel bootstrap. Docker/Procfile/Replit entry points invoke it; native hosting must enforce it separately. The guard was deliberately shown to fail on this Mac's 8.4.14. It is not a way to patch the computer.

Active-account checks intentionally deny pending users and may reveal previously incomplete test fixtures or account records. Legitimately active fixtures were made explicit; authorization was not weakened to restore tests. Stripe positives now sign raw request bodies; unsigned tests were not grandfathered in. Avatars need shared public storage. Roster CSV staging is encrypted in `roster_imports.source_csv` and cleared after parsing: workers need the same database and `APP_KEY`, not a newly invented shared plaintext upload disk. Prove both cross-process paths in staging.

## Verification and coverage limitations

Checks after the compromise precheck included Composer 2.9.1 validate/audit, npm 11.6.2 audit (all and omit-dev), PHPUnit 10.5.64, Pint, scoped Larastan/PHPStan, ESLint, Vitest 4.1.12, Vite 8.3.5, Gitleaks 8.24.3 (CI-pinned) plus 8.30.1 for wider ignored-file/current-scanner history coverage, and Trivy 0.76.1. Scanner downloads were SHA-256 checked against official release checksums, stored locally rather than globally installed. Package installs disabled lifecycle/plugins/scripts before reviewed application verification.

Reproducible scanner commands (set `AUDIT_DIR` to a private evidence directory; no secrets belong in arguments):

```bash
composer audit --locked --format=json
composer audit --format=json
npm audit --json
npm audit --omit=dev --json
gitleaks dir <nonignored-source-snapshot> --redact --gitleaks-ignore-path=/dev/null
gitleaks dir public/build --redact --gitleaks-ignore-path=/dev/null
bash .github/verify-gitleaks-history-policy.sh <gitleaks-8.24.3-binary>
trivy image --scanners vuln --format json --output "$AUDIT_DIR/final-image.json" <immutable-image-digest>
trivy fs --offline-scan --skip-db-update --scanners vuln --include-dev-deps --format cyclonedx \
  --skip-dirs node_modules --skip-dirs vendor --skip-dirs storage \
  --skip-dirs public/build --skip-dirs .git \
  --output "$AUDIT_DIR/lockfile-sbom-after.cdx.json" .
```

The optional `npm sbom --sbom-format cyclonedx` attempt failed with `EINVALIDPURLTYPE` because the private root package has no version; no fake release version was added to silence it. The offline Trivy lockfile CycloneDX export succeeded instead, with npm development dependencies enabled; that exporter's Composer development coverage is limited, so the full Composer inventory/audit remains a separate artifact. A focused extra PHPStan invocation initially hit the sandbox's loopback-listener restriction; retrying in serial `--debug` mode passed for both new runtime policy and modified Stripe controller, without an environment escalation. Container JSON reports (`php-base-trivy.json`, `node-base-trivy.json`, `postgres-base-trivy.json`) and before/after dependency inventories remain private evidence. The runtime floor and checklist final-signoff commands intentionally return failure on this unpatched/unapproved environment; structural checklist validation passes.

| Check | Result / boundary |
| --- | --- |
| `composer validate --strict` | Passed after lock refresh |
| `composer audit --locked --format=json` and installed audit | Zero reported advisories/abandoned packages; feed coverage not a security guarantee |
| `npm audit --json` and `npm audit --omit=dev --json` | Zero reported vulnerabilities after final install |
| Full backend suite | Passed on SQLite: 224 tests, 1,340 assertions. No patched serving runtime or PostgreSQL integration claim |
| Regression checks | Missing Stripe configuration, signed/unsigned events, inactive employee/admin/pending sessions, supported PHP floor boundaries, Vite host defaults |
| Pint / scoped static analysis | Passed; Larastan scope is `phpstan.neon`, not all PHP code or proof absence of injection |
| Frontend lint, unit tests, production build | Passed on patched local Node: ten tests, 203 transformed modules; updated real browser journeys not run locally against vulnerable PHP |
| Strict current source / bundle | CI-pinned Gitleaks scan of tracked + nonignored new/modified source snapshot and generated `public/build`, no history exceptions: zero findings |
| Wider ignored-file scan | 1,674 original detections: 1,663 PHPStan cache hashes, ten vendor examples/constants, one legacy-cache possible OpenAI key. **Not reported as a clean whole-workspace scan.** No new exception added |
| Full-history policy | Gitleaks 8.24.3: unignored baseline exactly three known fingerprints; exact approved ignore scan passes; fresh unrecognized synthetic finding fails. Additional 8.30.1 full-history scan with that same exact policy reports zero new findings. History unchanged |
| Official container base scans | PHP scanned with distribution-aware DB; Node/Postgres results retained separately. A final application image/SBOM and target-platform scan remain pending |
| Current-branch GitHub CI / E2E / live provider | Not run; changes were not pushed or deployed |

Source review included route capability/tenant policies, active-account/session paths, company billing ownership, provider webhooks, parameterized ORM/raw SQL uses, CSV encrypted staging and row scopes, avatar type/size/dimension validation and JPEG re-encoding, survey token expiry/hash/revocation, API response privacy/suppression, trusted host/proxy policy and response headers. `v-html` roster highlighting escapes text before marking matches; no new XSS finding was asserted merely from that directive. No application deserialization/shell tool pipeline or user-selected outbound URL was found in inspected app paths. No AI agent/MCP execution capability is implemented by this app; desktop connector access is separate and was not granted new permissions.

PostgreSQL tenants rely on application scopes/policies, not an independently demonstrated RLS policy. Direct database accounts therefore remain privileged access requiring network isolation and least privilege. CSP still allows inline scripts/styles for existing Blade code; nonces/strict CSP are a future hardening item, not claimed fixed. No exhaustive fuzzing, penetration test, host forensics, provider access-log review, external storage ACL assessment or independent human audit was performed.

## Production/staging checklist — release owner

- [ ] Select and inventory exact provider, project/service/environment, domain, web/worker/scheduler, DB, private/public storage, TLS edge, caches and backups. Prove which old previews exist; do not infer inventory from GitHub deployment records alone. Do not use the Railway connector.
- [ ] In trusted build infrastructure, inspect old install/build records against LiteLLM's wheel/image digests before reusing caches. If credible exposure appears, stop cache reuse and follow incident response, not just a dependency bump.
- [ ] Build the reviewed revision with `docker build --pull --no-cache -t empulse:audit-candidate .`; record immutable digest, platform and SBOM. Scan the **final** image with current Trivy and distribution advisories; disposition each high/critical native result by owner, prerequisites and patch/mitigation. No blanket ignore/VEX acceptance.
- [ ] Prove `php -v`, `php scripts/check-php-runtime.php`, serving SAPI executable/version, extension versions and active CA fingerprints in each web/worker/release image. Verify build Node version separately. Rebuild/restart old processes; tags/constraints alone do not close this gate.
- [ ] Use patched PostgreSQL >=16.15 and patched backup/restore clients. Verify private listener, no public DB port, SCRAM/TLS policy, app least-privilege role, backup/PITR and restore drill. `SELECT version();` through the normal secure administrative channel records the actual server without printing a connection string.
- [ ] Configure HTTPS edge/private origin, exact trusted proxies, secure cookies, debug off, immutable release SHA/environment identity, audit key, mail and Stripe signing secrets. Do not put replacement secrets into a potentially compromised environment.
- [ ] Provide the same database and encryption key to web/worker for database-backed encrypted roster staging, plus persistent public avatars. Verify synthetic import parse/commit/retention and normalized avatar serve/delete from every instance. Never expose source CSVs through the public disk.
- [ ] Run current-revision CI: patched PHP, PostgreSQL 16, clean migration/seed, all backend/JS/static/secret checks, fresh Playwright Chromium, role/tenant/negative/browser journeys. No previous SHA's green result substitutes for this.
- [ ] Use [the deployment runbook](PRODUCTION_DEPLOYMENT_RUNBOOK.md) for one-time migrations/catalog/cache and separate supervised process starts; do not seed production or migrate at image build/web startup.
- [ ] Prove the Docker loopback health probe works with production `APP_URL`/host validation. It now sends that configured canonical Host header rather than weakening trusted-host policy to admit arbitrary hosts; verify the final container actually becomes healthy.
- [ ] In isolated staging, verify deactivation invalidates an existing session; unsigned/missing-secret Stripe requests fail, genuine test events succeed once; Brevo shared-secret validation, invitation recovery and privacy suppression hold. Check access/billing logs without retaining secrets/employee answers.
- [ ] From outside the target, run `php artisan app:verify-deployment https://<host> <sha> <environment-id> --json`; verify live SHA/digest and worker/scheduler freshness. This command explicitly does not provide full production sign-off.
- [ ] Test rollback to a **patched** prior image, database compatibility/restore and provider failure alerts. Keep independent privacy/methodology/legal/accessibility and commercial launch decisions open until accountable approvals exist.

## Credential and incident-response checklist

No new shared credential was rotated. No new malicious installation or unauthorized account access was demonstrated. The following actions are conditional on actual exposure, except review of the newly found cache literal.

- [ ] OpenAI account owner: privately identify the legacy cache key without pasting it into a ticket/chat. Record key ID/project/owner, permissions, last use, affected scripts and whether disabled. If genuine/live, revoke at the issuer; inspect usage/billing/audit logs and coordinate other consumers before a replacement. A string replacement in this file is not revocation. Do not test the suspected key from the app or print it.
- [ ] Preserve timestamps, SHA-256, source/archive origin and available logs in restricted evidence storage. The original cache is untouched and excluded from Docker context. Do not execute it or delete it as a way to claim recovery.
- [ ] If a malicious LiteLLM wheel/image is identified, preserve original artifact/digest and UTC install/build times; quarantine the environment from serving/builds, assess persistence, and rebuild/reimage from known-clean artifacts. Investigate Python startup hooks even without imports. Vendor known malicious container digest includes `sha256:1bbcb1443bc962e45f5aad53fe3d2c30600e0379715fb6c664cdd3ac126c339e`; consult vendor for the full evolving set and windows.
- [ ] Identify issuing services and permissions of every secret accessible to an exposed process: GitHub PAT/OAuth and Actions tokens, cloud/registry/storage/DB, Stripe, Brevo, app/session/audit secrets, OAuth and any AI-provider keys. Review access/usage/billing logs; revoke old credentials, temporary sessions and refresh tokens as appropriate. Coordinate shared consumers across projects; do not rotate them independently here.
- [ ] Use a trusted clean device/session to provision replacements only after containment; deploy them through the secret manager and verify old-token rejection without printing values. APP_KEY rotation has encrypted-data/session consequences and needs a tested recovery plan, not casual regeneration.
- [ ] Retain Brevo R-004 as **accepted_risk**: owner-confirmed dead credential remains in old commits. Do not rewrite history or expand `.gitleaksignore`. Reassess only under its documented triggers.

## One-time device/account/network checklist — outside repo authority

- [ ] Device administrator: plan PHP-FPM upgrade and supervised restart. Check `brew list --versions php`, `php -v`, `lsof -nP -iTCP:9000 -sTCP:LISTEN`, then the serving process executable, before/after. Do not assume the separately installed PHP 8.5.11 is fixed (it is below 8.5.12). No global change was performed by this audit.
- [ ] DB administrator: identify consumers of running PostgreSQL 14.21 and patch to >=14.24 within that major, or perform a separately planned PG16 migration. Installed PG16 alone does not replace the running service. Preserve backups/data directories and avoid interrupting unrelated projects.
- [ ] Update Chrome through About Google Chrome and relaunch; check macOS Software Update and vendor releases for Codex/ChatGPT/Electron, browsers, CLI/package managers and MCP plugins. Local Chrome 155 is behind the October stable 156 generation. Do not execute unknown MCP packages simply to get a version.
- [ ] Repair bundled trust stores independently: common Python pip/certifi are current, older isolated tools are not. Run their vendor-supported updater using a trusted existing CA channel, then verify each embedded bundle. Node 22.23.4's bundle and PHP's Homebrew CA file exclude affected roots; `/etc/ssl/cert.pem` containing C1/C3 does not by itself establish macOS's dynamic Keychain policy. Obtain OS/browser vendor distrust evidence. Do not remove safe G1 or disable TLS verification.
- [ ] Repo owner: GitHub Settings → Rules/Rulesets or Branches → protect main with reviewed PRs and required current CI/secret checks; Settings → Actions → restrict allowed Actions and require full SHA pins where available. Review collaborators, MFA/passkeys, OAuth apps, deploy keys and audit/security alerts. Do not paste tokens into issue descriptions.
- [ ] Network owner: inventory DNS resolver/authoritative services, routers/VPN/security appliances, Snort, Cisco products, Splunk SOAR, QUIC edge/library and Wireshark versions. Apply exact vendor/model/branch fixes only if present. Provider attestation can close managed-service gaps; repo searches cannot.
- [ ] If Oracle/.NET/Signal tooling is actually installed in a relevant build/control path, identify vendor/product/version and apply its advisory. No relevant app dependency was found, so no broad Java/.NET/Signal or Bluetooth change was made.

## Confidence and closure

High confidence: exact local package/runtime versions, the two pre-fix application failures and regression repairs, narrow history exception preservation, and local test/scan outcomes. Medium confidence: inspected source-path coverage and present-file compromise precheck; neither is exhaustive. Low/no confidence claimed for unknown remote environments, historical erased artifacts, provider secrets/logs, appliance estate, final image, and actual deployment.

Close host findings only with new serving-process/DB versions and deliberate restart evidence; close the cache-key finding with issuer-owner identity/revocation/log evidence; close container findings with a final digest/SBOM, vendor-aware individual dispositions and release review; close current-revision qualification with exact-SHA CI/E2E; close deployment gaps with provider inventory and externally verified served SHA/runtime. Independent launch approvals remain separate. An audit cannot prove that every possible vulnerability has been found.
