/* Daily aggregate filtering. Dates are application dates; no applicant identities are needed. */
(function (root) {
    const metrics = ['applicants', 'male', 'female', 'scholars', 'pwd', 'ssen', 'noaIssued', 'paidExam', 'reserved', 'took', 'passed', 'enrolled'];
    const empty = () => Object.fromEntries(metrics.map(key => [key, 0]));
    function bounds(range, today) {
        const end = new Date(today + 'T00:00:00Z'), start = new Date(end);
        if (range === 'This Week') start.setUTCDate(start.getUTCDate() - (start.getUTCDay() + 6) % 7);
        else if (range === 'This Month') start.setUTCDate(1);
        else if (range === 'This Semester') { start.setUTCMonth(start.getUTCMonth() < 6 ? 0 : 6, 1); }
        else return null;
        return [start.toISOString().slice(0, 10), end.toISOString().slice(0, 10)];
    }
    function select(feed, scope, year = scope.year, dates = bounds(scope.range, feed.today)) {
        return feed.rows.filter(row => row.year === year
            && (!scope.department || scope.department === 'All Departments' || row.department === scope.department)
            && (!dates || (row.date && row.date >= dates[0] && row.date <= dates[1])));
    }
    function totals(rows) {
        return rows.reduce((sum, row) => { metrics.forEach(key => { sum[key] += Number(row[key] || 0); }); return sum; }, empty());
    }
    function group(rows, dimension, metric = 'applicants') {
        return rows.reduce((sum, row) => { sum[row[dimension]] = (sum[row[dimension]] || 0) + Number(row[metric] || 0); return sum; }, {});
    }
    function previousYearTotals(feed, scope) {
        const match = /^(\d{4})-(\d{4})$/.exec(scope.year);
        if (!match) return null;
        const year = `${Number(match[1]) - 1}-${Number(match[2]) - 1}`;
        if (!feed.years.includes(year)) return null;
        const dates = bounds(scope.range, feed.today)?.map(value => {
            const date = new Date(value + 'T00:00:00Z'), month = date.getUTCMonth();
            date.setUTCFullYear(date.getUTCFullYear() - 1);
            if (date.getUTCMonth() !== month) date.setUTCDate(0); // February 29 -> February 28
            return date.toISOString().slice(0, 10);
        }) ?? null;
        return totals(select(feed, scope, year, dates));
    }
    const api = { select, totals, group, bounds, previousYearTotals };
    root.IARIS_ANALYTICS_FILTERS = api;
    if (typeof module !== 'undefined') module.exports = api;
})(typeof window === 'undefined' ? globalThis : window);
