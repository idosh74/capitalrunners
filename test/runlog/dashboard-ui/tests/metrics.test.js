import test from 'node:test';
import assert from 'node:assert/strict';
import { formatPace, getInsights, getLeaders, getTrend, offsetDate, sumDays } from '../src/metrics.js';

const days = [
  { day: '2026-03-08', distance: '2', sessions: '1', pace_distance: '2', pace_seconds: '600' },
  { day: '2026-03-10', distance: '13', sessions: '3', pace_distance: '12', pace_seconds: '3600' },
];

test('date bounds, zero-filled trend, and totals', () => {
  assert.equal(offsetDate('2026-03-01', -1), '2026-02-28');
  assert.deepEqual(sumDays(days, '2026-03-09', '2026-03-10'), { distance: 13, sessions: 3 });
  assert.deepEqual(getTrend(days, '2026-03-10', 3).map(item => item.distance), [2, 0, 13]);
});

test('weekday insights and distance-weighted pace', () => {
  const insights = getInsights(days, '2026-03-10', 3);
  assert.equal(insights.busiest, 2);
  assert.deepEqual(insights.weekdays, [2, 0, 13, 0, 0, 0, 0]);
  assert.equal(formatPace(insights.pace), '5:00');
  assert.equal(formatPace(getInsights([], '2026-03-10', 7).pace), '—');
  assert.equal(formatPace(359.7), '6:00');
});

test('leaderboard changes by period, omits zeroes, and sorts ties', () => {
  const leaders = [
    { id: 1, name: 'ב', today: 2, week: 5, month: 8 },
    { id: 2, name: 'א', today: 0, week: 5, month: 9 },
    { id: 3, name: 'ג', today: 3, week: 2, month: 10 },
    { id: 4, name: 'ד', today: 4, week: 1, month: 1 },
  ];
  assert.deepEqual(getLeaders(leaders, 'today').map(item => item.id), [4, 3, 1]);
  assert.deepEqual(getLeaders(leaders, 'week').map(item => item.id), [2, 1, 3]);
  assert.deepEqual(getLeaders(leaders, 'month').map(item => item.id), [3, 2, 1]);
});
