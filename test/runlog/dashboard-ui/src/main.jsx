import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { formatPace, getInsights, getLeaders, getTrend, offsetDate, sumDays } from './metrics.js';
import './style.css';

const number = value => new Intl.NumberFormat('he-IL', { maximumFractionDigits: 1 }).format(value);
const dayNames = ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שבת'];
const periods = { today: 'היום', week: 'השבוע', month: 'החודש' };
const activityRotationMs = 4500;
const demoEvents = [
  { title: 'ריצה ארוכה קבוצתית', detail: 'שבת · אימון לדוגמה' },
  { title: 'אימון מהירות', detail: 'שלישי · אימון לדוגמה' },
  { title: 'תחרות ריצה', detail: 'אירוע לדוגמה' },
];

function Selector({ label, value, options, onChange }) {
  return <div className="selector" role="group" aria-label={label}>
    {Object.entries(options).map(([key, text]) =>
      <button key={key} type="button" className={String(value) === key ? 'selected' : ''} aria-pressed={String(value) === key} onClick={() => onChange(key)}>{text}</button>
    )}
  </div>;
}

function Metric({ label, value, sub, icon, tone }) {
  return <article className={`metric ${tone}`}><div className="metric-top"><span>{label}</span><span className="metric-icon" aria-hidden="true">{icon}</span></div>
    <strong>{value}</strong><p>{sub}</p></article>;
}

function TrendChart({ trend }) {
  const max = Math.max(...trend.map(point => point.distance), 1);
  const points = trend.map((point, index) => {
    const x = trend.length === 1 ? 0 : (index / (trend.length - 1)) * 600;
    return `${x.toFixed(2)},${(140 - point.distance / max * 115).toFixed(2)}`;
  }).join(' ');
  const total = trend.reduce((sum, day) => sum + day.distance, 0);
  return <div className="trend-wrap">
    <div className="chart-summary"><strong>{number(total)} <small>ק״מ</small></strong><span>סך המרחק בתקופה</span></div>
    <svg viewBox="0 0 600 155" preserveAspectRatio="none" className="trend-chart" role="img" aria-label={`סך המרחק בתקופה ${number(total)} קילומטרים`}>
      {[30, 70, 110, 140].map(y => <line key={y} x1="0" x2="600" y1={y} y2={y} stroke="currentColor" className="gridline" />)}
      <polyline points={points} fill="none" className="trend-line" vectorEffect="non-scaling-stroke" />
    </svg>
    <div className="chart-axis" dir="ltr"><span>{trend[0].date.split('-').reverse().join('/')}</span><span>{trend.at(-1).date.split('-').reverse().join('/')}</span></div>
  </div>;
}

function RadarChart({ runners, memberId, selectedRunnerId, onRunnerChange, range }) {
  const runnerId = selectedRunnerId || memberId;
  const selected = runners.find(runner => String(runner.id) === String(runnerId));
  const values = selected ? [
    { label: 'התמדה', value: selected.persistence, icon: '🔥' },
    { label: 'עוצמה', value: selected.intensity, icon: '⚡' },
    { label: 'מחויבות', value: selected.commitment, icon: '🎯' },
  ] : [];
  const hasAllValues = values.every(item => item.value !== null);
  const max = Math.max(150, ...values.map(item => item.value || 0));
  const scaleMax = Math.ceil(max / 50) * 50;
  const center = { x: 180, y: 132 };
  const radius = 108;
  const pointFor = (value, index) => {
    const angle = (-90 + index * 120) * Math.PI / 180;
    const scaled = Math.min(Number(value), scaleMax) / scaleMax * radius;
    return { x: center.x + Math.cos(angle) * scaled, y: center.y + Math.sin(angle) * scaled };
  };
  const polygon = values.map((item, index) => {
    const point = pointFor(item.value, index);
    return `${point.x.toFixed(2)},${point.y.toFixed(2)}`;
  }).join(' ');
  const labels = [
    { x: 180, y: 8, textAnchor: 'middle', icon: '🔥', label: 'התמדה' },
    { x: 312, y: 190, textAnchor: 'middle', icon: '⚡', label: 'עוצמה' },
    { x: 48, y: 190, textAnchor: 'middle', icon: '🎯', label: 'מחויבות' },
  ];
  return <div className="radar-wrap">
    <div className="radar-toolbar">
      <label>הרץ
        <select value={selected ? String(selected.id) : ''} onChange={event => onRunnerChange(event.target.value)} disabled={!runners.length}>
          <option value="" disabled>בחירת רץ או רצה</option>
          {runners.map(runner => <option key={runner.id} value={String(runner.id)}>{runner.name}</option>)}
        </select>
      </label>
      <span className="muted">תקופה: {range} ימים · 100 = ממוצע הקבוצה בהתמדה ובעוצמה</span>
    </div>
    {selected ? <>
      {hasAllValues ? <svg viewBox="0 0 360 230" className="radar-chart" role="img" aria-label={`מדדי הקבוצה של ${selected.name}`}>
        {[0.25, 0.5, 0.75, 1].map(ratio => {
          const ring = values.map((item, index) => {
            const point = pointFor(scaleMax * ratio, index);
            return `${point.x.toFixed(2)},${point.y.toFixed(2)}`;
          }).join(' ');
          return <polygon key={ratio} points={ring} fill="none" className="radar-grid" />;
        })}
        {values.map((item, index) => {
          const end = pointFor(scaleMax, index);
          return <line key={item.label} x1={center.x} y1={center.y} x2={end.x} y2={end.y} className="radar-axis" />;
        })}
        {scaleMax > 100 && <polygon points={values.map((item, index) => {
          const point = pointFor(100, index);
          return `${point.x.toFixed(2)},${point.y.toFixed(2)}`;
        }).join(' ')} fill="none" className="radar-average" />}
        <polygon points={polygon} fill="currentColor" className="radar-area" />
        {values.map((item, index) => {
          const point = pointFor(item.value, index);
          return <circle key={item.label} cx={point.x} cy={point.y} r="5" className="radar-point" />;
        })}
        {labels.map(item => <text key={item.label} x={item.x} y={item.y} textAnchor={item.textAnchor} className="radar-label">
          <tspan className="radar-emoji">{item.icon}</tspan><tspan dx="7">{item.label}</tspan>
        </text>)}
      </svg> : <p className="empty">לא ניתן לחשב עוצמה ומחויבות כאשר ממוצע המרחק לאימון הוא אפס.</p>}
      <div className="radar-scores">{values.map(item => <div key={item.label}><span>{item.icon} {item.label}</span><strong>{item.value === null ? '—' : number(item.value)}</strong></div>)}</div>
    </> : <p className="empty">{runners.length ? 'הרץ הנבחר אינו עומד בסף של 20 אימונים בתקופה זו. ניתן לבחור רץ אחר.' : 'אין רצים פעילים עם לפחות 20 אימונים בתקופה זו.'}</p>}
  </div>;
}

function ActivityFeed({ activities }) {
  const [index, setIndex] = useState(0);
  const [paused, setPaused] = useState(() => window.matchMedia('(prefers-reduced-motion: reduce)').matches);
  const [hovered, setHovered] = useState(false);
  useEffect(() => {
    if (paused || hovered || activities.length < 2) return;
    const timer = window.setTimeout(() => setIndex(current => (current + 1) % activities.length), activityRotationMs);
    return () => window.clearTimeout(timer);
  }, [index, activities.length, paused, hovered]);
  const activity = activities[index];
  return <section className="panel activity-panel"
    onMouseEnter={() => setHovered(true)}
    onMouseLeave={() => setHovered(false)}
    onFocusCapture={event => {
      if (!event.currentTarget.contains(event.relatedTarget)) setPaused(true);
    }}>
    <div className="section-title"><div><span className="eyebrow">מתאמנים יחד</span><h2>האימונים של היום</h2></div>
      {activities.length > 1 && <div className="activity-controls">
        <button type="button" aria-label="האימון הקודם" onClick={() => setIndex((index + activities.length - 1) % activities.length)}><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" /></svg></button>
        <span dir="ltr">{index + 1} / {activities.length}</span>
        <button type="button" aria-label="האימון הבא" onClick={() => setIndex((index + 1) % activities.length)}><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M15 5l-7 7 7 7" /></svg></button>
        <button type="button" aria-label={paused ? 'הפעלת מעבר אוטומטי' : 'עצירת מעבר אוטומטי'} onClick={() => setPaused(!paused)}>{paused ? '▶' : '❚❚'}</button>
      </div>}</div>
    {activity ? <div className="activity-card">
      <div className="activity-head"><div className="avatar">{activity.name.slice(0, 1)}</div><div><h3>{activity.name}</h3><span className="muted">{activity.type}</span></div><span className="badge">היום</span></div>
      <div className="activity-stats"><div><span>מרחק כולל</span><strong>{number(Number(activity.distance))} <small>ק״מ</small></strong></div><div><span>זמן תרגיל</span><strong dir="ltr">{formatDuration(Number(activity.run_time))}</strong></div><div><span>קצב תרגיל</span><strong dir="ltr">{activity.run_distance > 0 && activity.run_time > 0 ? formatPace(Number(activity.run_time) / Number(activity.run_distance)) : '—'}</strong></div></div>
      {activity.notes && <div className="activity-description"><span>תיאור האימון</span><p dir="auto">{activity.notes}</p></div>}
      <p className="muted">קצב וזמן מתייחסים לתרגיל, ללא תוספת המרחק.</p>
    </div> : <p className="empty">עדיין לא נרשמו אימונים עם מרחק היום.</p>}
    <a className="text-link" href="feed.php">לכל האימונים בפיד ←</a>
  </section>;
}

function formatDuration(seconds) {
  if (!Number.isFinite(seconds) || seconds <= 0) return '—';
  const rounded = Math.round(seconds);
  return `${Math.floor(rounded / 3600)}:${String(Math.floor(rounded % 3600 / 60)).padStart(2, '0')}:${String(rounded % 60).padStart(2, '0')}`;
}

function Leaderboard({ leaders }) {
  const [period, setPeriod] = useState('week');
  const top = getLeaders(leaders, period);
  return <section className="panel"><div className="section-title"><div><span className="eyebrow">מובילים במרחק</span><h2>טבלת הקבוצה</h2></div><span className="symbol">♛</span></div>
    <Selector label="תקופת הדירוג" value={period} options={periods} onChange={setPeriod} />
    <p className="muted detail">שלושת הרצים הפעילים שרשמו את המרחק הרב ביותר</p>
    {top.length ? <ol className="leaders">{top.map((runner, index) => <li key={runner.id} className={`leader rank-${index + 1}`}><span className="rank">{index + 1}</span><div className="leader-name"><strong>{runner.name}</strong><span>רץ או רצה פעילים</span></div><div className="leader-distance"><strong>{number(runner.distance)}</strong><small>ק״מ</small></div></li>)}</ol>
      : <p className="empty">אין מרחקים מדווחים לתקופה זו.</p>}
  </section>;
}

function App() {
  const [data, setData] = useState(null);
  const [error, setError] = useState('');
  const [retry, setRetry] = useState(0);
  const [range, setRange] = useState('30');
  const [selectedRunnerId, setSelectedRunnerId] = useState('');
  const [selectedDate, setSelectedDate] = useState('');
  useEffect(() => {
    const controller = new AbortController();
    setError('');
    const params = new URLSearchParams({ range });
    if (selectedDate) params.set('date', selectedDate);
    const url = `php/get_dashboard_data.php?${params.toString()}`;
    fetch(url, { signal: controller.signal, credentials: 'same-origin', cache: 'no-store' })
      .then(response => { if (!response.ok) throw new Error(`HTTP ${response.status}`); return response.json(); })
      .then(result => { if (Number(result.status?.ecode) !== 1 || !result.data) throw new Error(result.status?.emessage || 'תגובה לא תקינה'); setData(result.data); })
      .catch(err => { if (err.name !== 'AbortError') setError(err.message); });
    return () => controller.abort();
  }, [retry, selectedDate, range]);
  function changeDate(date) {
    if (date === selectedDate) return;
    setData(null);
    setError('');
    setSelectedDate(date);
  }
  if (error) return <main className="state"><h1>לא ניתן לטעון את לוח הקבוצה</h1><p>{error}</p><button type="button" onClick={() => setRetry(retry + 1)}>ניסיון נוסף</button>{selectedDate && <button type="button" onClick={() => changeDate('')}>חזרה להיום</button>}<a href="runlog.php">חזרה ליומן</a></main>;
  if (!data) return <main className="state" role="status">טוען את לוח הקבוצה...</main>;
  const todayData = data.days.find(day => day.day === data.today);
  const today = sumDays(data.days, data.today, data.today);
  const week = sumDays(data.days, data.week_start, data.today);
  const month = sumDays(data.days, data.month_start, data.today);
  const insights = getInsights(data.days, data.today, Number(range));
  const trend = getTrend(data.days, data.today, Number(range));
  const previousMonth = sumDays(data.days, offsetDate(data.month_start, -1).slice(0, 7) + '-01', offsetDate(data.month_start, -1));
  return <>
    <header className="topbar"><a className="brand" href="runlog.php"><span className="brand-icon">↗</span><span>רצי הבירה<small>לוח הקבוצה</small></span></a><nav aria-label="ניווט ראשי"><a href="runlog.php">יומן ריצה</a><a href="feed.php">מה עשו החבר׳ה</a><a href="statistics.php">סטטיסטיקה</a><a href="logout.php">התנתקות</a></nav></header>
    <main className="dashboard">
      <section className="test-date" aria-label="תאריך בדיקה">
        <div><strong>מצב בדיקה</strong><p>הנתונים מוצגים נכון לתאריך שנבחר. הרצים הפעילים נקבעים לפי מצבם הנוכחי.</p></div>
        <label>תאריך לתצוגה <input type="date" min="1000-01-01" max={data.current_date} value={data.today} onChange={event => { if (event.target.value) changeDate(event.target.value); }} /></label>
        {data.today !== data.current_date && <button type="button" onClick={() => changeDate('')}>חזרה להיום</button>}
      </section>
      <section className="hero"><div><span className="eyebrow">הקבוצה שלנו · לוח הפעילות</span><h1>שלום {data.member_name},<br /><span>רצים ביחד קדימה.</span></h1><p>כל האימונים, המרחקים וההישגים של הקבוצה במקום אחד.</p><div className="hero-actions"><a className="primary-button" href="runlog.php">יומן הריצה שלי ←</a><a className="secondary-button" href="feed.php">לפיד הקבוצה</a></div></div><div className="hero-art" aria-hidden="true"><div className="hero-circle">↗</div><span>EVERY RUN<br />COUNTS.</span></div></section>
      <div className="metrics"><Metric label="רצים פעילים היום" value={number(Number(todayData?.active_runners || 0))} sub={`מתוך ${number(Number(data.active_runners))} רצים פעילים`} icon="◉" tone="green" />
        <Metric label="היום" value={`${number(today.distance)} ק״מ`} sub={`${number(today.sessions)} אימונים עם מרחק`} icon="◷" tone="blue" />
        <Metric label="השבוע" value={`${number(week.distance)} ק״מ`} sub={`${number(week.sessions)} אימונים · מיום ראשון`} icon="▦" tone="orange" />
        <Metric label="החודש" value={`${number(month.distance)} ק״מ`} sub={`${number(month.sessions)} אימונים מתחילת החודש`} icon="▤" tone="green" /></div>
      <div className="workspace"><div className="main-column"><section className="panel">
        <div className="section-title"><div><span className="eyebrow">מדדי ביצוע לעומת ממוצע הקבוצה</span><h2>פרופיל הרץ</h2></div><Selector label="תקופת הגרף" value={range} options={{ 7: '7 ימים', 30: '30 ימים', 90: '90 ימים', 365: 'שנה' }} onChange={value => { if (value !== range) { setData(null); setRange(value); } }} /></div>
        <RadarChart runners={data.measure_runners || []} memberId={data.member_id} selectedRunnerId={selectedRunnerId} onRunnerChange={setSelectedRunnerId} range={range} />
      </section><ActivityFeed activities={data.activities} /></div>
      <aside className="side-column"><Leaderboard leaders={data.leaders} /><section className="panel"><div className="section-title"><div><span className="eyebrow">מתוך הנתונים</span><h2>תובנות הקבוצה</h2></div><span className="symbol">✦</span></div>
        <div className="insights"><div><span>היום המוביל במרחק</span><strong>{insights.busiest === null ? '—' : `יום ${dayNames[insights.busiest]}`}</strong><small>בתקופה שנבחרה</small></div><div><span>קצב תרגיל ממוצע</span><strong dir="ltr">{formatPace(insights.pace)}</strong><small>דקות לק״מ · בתקופה שנבחרה</small></div><div><span>ממוצע מרחק שבועי</span><strong>{number(trend.reduce((sum, day) => sum + day.distance, 0) * 7 / Number(range))} ק״מ</strong><small>על פני התקופה שנבחרה</small></div><div><span>החודש הקודם</span><strong>{number(previousMonth.distance)} ק״מ</strong><small>{number(previousMonth.sessions)} אימונים</small></div></div>
      </section><section className="panel"><div className="section-title"><div><span className="eyebrow">להמחשה בלבד</span><h2>אירועים קרובים</h2></div><span className="symbol">▦</span></div><p className="demo-notice">אירועים לדוגמה בלבד — אינם מבוססים על נתוני הקבוצה.</p><div className="demo-events">{demoEvents.map(event => <div key={event.title}><span className="event-icon">◷</span><div><strong>{event.title}</strong><small>{event.detail}</small></div><span className="demo-badge">דוגמה</span></div>)}</div></section></aside></div>
      <footer>רצי הבירה · נתוני מרחק לאימונים עם מרחק בלבד · השבוע מתחיל ביום ראשון</footer>
    </main>
  </>;
}

createRoot(document.getElementById('dashboard-root')).render(<App />);
