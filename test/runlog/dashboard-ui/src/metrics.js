const DAY = 86400000;

export function offsetDate(date, offset) {
  const timestamp = Date.parse(`${date}T00:00:00Z`);
  return new Date(timestamp + offset * DAY).toISOString().slice(0, 10);
}

export function sumDays(days, start, end) {
  return days.reduce((total, day) => {
    if (day.day >= start && day.day <= end) {
      total.distance += Number(day.distance);
      total.sessions += Number(day.sessions);
    }
    return total;
  }, { distance: 0, sessions: 0 });
}

export function getTrend(days, today, range) {
  const byDate = new Map(days.map(day => [day.day, day]));
  return Array.from({ length: range }, (_, index) => {
    const date = offsetDate(today, index - range + 1);
    return { date, distance: Number(byDate.get(date)?.distance || 0) };
  });
}

export function getLeaders(leaders, period) {
  return leaders
    .map(runner => ({ ...runner, distance: Number(runner[period]) }))
    .filter(runner => runner.distance > 0)
    .sort((a, b) => b.distance - a.distance || a.name.localeCompare(b.name, 'he') || Number(a.id) - Number(b.id))
    .slice(0, 3);
}

export function getInsights(days, today, range) {
  const start = offsetDate(today, 1 - range);
  const weekdays = Array(7).fill(0);
  let distance = 0;
  let seconds = 0;
  for (const day of days) {
    if (day.day < start || day.day > today) continue;
    weekdays[new Date(`${day.day}T00:00:00Z`).getUTCDay()] += Number(day.distance);
    distance += Number(day.pace_distance);
    seconds += Number(day.pace_seconds);
  }
  const max = Math.max(...weekdays);
  return { weekdays, busiest: max > 0 ? weekdays.indexOf(max) : null, pace: distance > 0 ? seconds / distance : null };
}

export function formatPace(seconds) {
  if (seconds == null || !Number.isFinite(seconds)) return '—';
  const rounded = Math.round(seconds);
  return `${Math.floor(rounded / 60)}:${String(rounded % 60).padStart(2, '0')}`;
}
