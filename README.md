# SAHARA Core Services
PHP ^8.2 framework-independent local calculations. PSR-4 namespace Sahara\Core. Install as a versioned Composer VCS library; application code must not duplicate src/.

Run `composer test` and `composer lint`. No network, credentials or database needed for calculations/tests.

Qibla: initial great-circle bearing relative to true north and spherical distance; direction undefined at Kaaba/antipode. Hijri: civil Friday epoch and astronomical Thursday epoch tabular methods, explicitly calculated. Ramadan: calculated dates plus validated country/year official overrides with source; no worldwide religious authority claimed. Prayer: solar approximation (1800–2200), twilight angles or Isha interval, Asr factor, high latitude rules and per-event minute corrections. Results are timestamped in requested timezone; polar events may be null with warnings. Choose a locally accepted method and official declaration where applicable.

See [algorithm notes](docs/ALGORITHMS.md). External adapters can implement Contracts without changing consumers.
