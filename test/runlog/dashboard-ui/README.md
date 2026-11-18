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

Statistics count sessions with a positive total distance through today.
The test-only date picker changes the dashboard's reference date without
changing the database; it cannot select a future date. "Today", "this week"
and "this month" refer to that selected date. Runner active status and the
active-runner total reflect the current database state, not historical status.
The week starts on Sunday; the month starts on its first day. Leaderboards
include active runners and rank the three highest distances for today,
the current week or month. The activity panel shows up to ten of today's
sessions. The graph and weekday insights use the selected period, and average
pace is total exercise time divided by total exercise distance for sessions
where both values are positive. Upcoming events are clearly labelled examples;
they are not stored or connected to an event calendar.

The activity carousel advances every 4.5 seconds when multiple sessions exist.
It pauses while hovered or when keyboard focus enters it; use its play button to
resume. Users who prefer reduced motion start with rotation paused.
