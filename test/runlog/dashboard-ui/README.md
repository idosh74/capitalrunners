# לוח הקבוצה

The React dashboard is embedded in `../dashboard.php` and uses the authenticated
`../php/get_dashboard_data.php` endpoint. It is separate from the production site.

To install dependencies and rebuild the assets:

```sh
cd test/runlog/dashboard-ui
npm ci
npm test
npm run build
php ../tests/dashboard_data_test.php
```

Deploy `dashboard.php`, the dashboard PHP endpoint and helper, the navigation
link in `widgets/header.php`, and the `dist/` directory, including its
third-party license notices. Node.js is needed only for development/building.

Distance statistics count sessions with a positive total distance through today.
The test-only date picker changes the dashboard's reference date without
changing the database; it cannot select a future date. "Today", "this week"
and "this month" refer to that selected date. Runner active status and the
active-runner total reflect the current database state, not historical status.
The week starts on Sunday; the month starts on its first day. Leaderboards
include active runners and rank the three highest distances for today,
the current week or month. The activity panel shows up to ten of today's
sessions, including their workout descriptions when present. The radar and group insights use the selected period, and average
pace is total exercise time divided by total exercise distance for sessions
where both values are positive. Upcoming events are clearly labelled examples;
they are not stored or connected to an event calendar.

The radar offers 30-, 90-, and 180-day periods and includes only currently active (visible profile) runners with at
least 20 recorded events in the selected period, including events with zero
distance. The period ends on the selected reference date (today by default).
Changing the period reloads the radar without resetting the rest of the dashboard.
Distance for these measures is run distance plus cooldown, excluding warmup.
Distance per workout is rounded to two decimals before calculating the
unweighted group average. Persistence and intensity are rounded to whole
numbers, and commitment is the rounded geometric mean of those indices.
When the group's average distance per workout is zero, intensity and commitment
are unavailable. A runner below the threshold is not included in the comparison.
The average commitment index is not necessarily 100.

The activity carousel advances every 4.5 seconds when multiple sessions exist.
It pauses while hovered or when keyboard focus enters it; use its play button to
resume. Users who prefer reduced motion start with rotation paused.
