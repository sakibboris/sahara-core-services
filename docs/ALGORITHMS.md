# Algorithm notes
Version 0.1.0 / schema 1. Original PHP implementation of mathematical formulas, not a vendored provider response or library copy.

Qibla uses the spherical initial-bearing equation to 21.4225 N, 39.8262 E, mean Earth radius 6371.0088 km. This is true north, not magnetic compass heading.

Hijri tabular calendar uses alternating 30/29-day months and 11 leap years in a 30-year cycle. civil uses Gregorian 0622-07-19 epoch; tabular-astronomical uses 0622-07-18. Neither is observational Umm al-Qura. Date-only conversion does not apply a sunset boundary. Month/year/day validation precedes conversion.

Prayer solar longitude, obliquity, right ascension and declination follow published approximate solar mathematics. Three iterations solve altitude crossings; UTC instants convert with IANA timezone offsets at each event. Standard horizon is -0.833 degrees; elevation/refraction variability is not modeled. Asr factor is selectable. Adjacent sunrise/sunset events define high-latitude night portions. Missing polar sun is explicitly unavailable; no fabricated fallback. Custom Isha intervals (including Ramadan) must be selected by application configuration. Minute offsets are applied last and may intentionally change order.
References: https://aa.usno.navy.mil/faq/sun_approx and https://praytimes.org/docs/calculation. Tests use own acceptance windows and mathematical invariants; remote comparison is opt-in and responses are not committed.

Official Ramadan overrides require country/year, 29/30 days, source and HTTPS reference within seven days of the selected method. Duplicate overrides reject. Confirmation is an input assertion by an authorized administrator; the engine does not validate a religious declaration's authenticity.
