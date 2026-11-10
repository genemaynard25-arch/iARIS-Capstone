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
    function select(feed, scope, year = scope.year) {
        const dates = bounds(scope.range, feed.today);
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
    const api = { select, totals, group, bounds };
    root.IARIS_ANALYTICS_FILTERS = api;
    if (typeof module !== 'undefined') module.exports = api;
})(typeof window === 'undefined' ? globalThis : window);
