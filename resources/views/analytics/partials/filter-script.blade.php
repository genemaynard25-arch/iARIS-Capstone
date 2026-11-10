        // One scope drives the figures, charts, table views, and CSV exports together.
        const filterEngine = window.IARIS_ANALYTICS_FILTERS;
        const countFields = ['male', 'female', 'scholars', 'paidExam', 'reserved', 'noaIssued', 'pwd', 'ssen'];
        function applyAnalyticsFilters() {
            const scope = currentView();
            const years = [...FILTER_DATA.years].reverse();
            const previousYear = years[years.indexOf(scope.year) - 1];
            const selected = filterEngine.select(FILTER_DATA, scope);
            const current = filterEngine.totals(selected);
            const previous = filterEngine.totals(filterEngine.select(FILTER_DATA, scope, previousYear || ''));
            const rate = (value, total) => total ? value / total * 100 : 0;
            const values = [format(current.applicants), rate(current.passed, current.took).toFixed(1) + '%', rate(current.reserved, current.applicants).toFixed(1) + '%', format(current.enrolled)];
            const changes = [
                previous.applicants ? (current.applicants - previous.applicants) / previous.applicants * 100 : null,
                previous.took ? rate(current.passed, current.took) - rate(previous.passed, previous.took) : null,
                previous.applicants ? rate(current.reserved, current.applicants) - rate(previous.reserved, previous.applicants) : null,
                previous.enrolled ? (current.enrolled - previous.enrolled) / previous.enrolled * 100 : null,
            ];
            document.querySelectorAll('[data-analytics-stat]').forEach((card, index) => {
                card.querySelector('[data-stat-value]').textContent = values[index];
                const delta = card.querySelector('[data-stat-change]'), change = changes[index];
                if (index === 1) {
                    delta.textContent = `${format(current.passed)} of ${format(current.took)} who took it`;
                    delta.className = 'small fw-semibold text-body';
                    return;
                }
                delta.textContent = change === null ? 'No matching prior-year data' : `${change >= 0 ? '+' : ''}${change.toFixed(1)}${index === 1 || index === 2 ? ' pts' : '%'} vs prior AY`;
                delta.className = 'small ' + (change === null ? 'text-body-secondary' : 'fw-semibold ' + (change >= 0 ? 'text-primary' : 'text-danger'));
            });
            const programs = Object.entries(filterEngine.group(selected, 'department')).sort((a, b) => b[1] - a[1]).slice(0, 5);
            CHARTS.programs = { labels: programs.map(x => x[0]), values: programs.map(x => x[1]) };
            const locations = filterEngine.group(selected, 'location');
            CHARTS.locations = { labels: Object.keys(locations), values: Object.values(locations) };
            CHARTS.funnel.values = [current.applicants, current.took, current.passed, current.reserved, current.enrolled];
            const funnelColors = ['#00503D', '#00674F', '#2E8A6E', '#4DAB8E', '#7FC4AB'];
            document.querySelectorAll('#filteredFunnel li').forEach((row, i) => {
                const value = CHARTS.funnel.values[i], share = rate(value, current.applicants);
                row.querySelector('.ar-funnel-value').textContent = format(value);
                row.querySelector('.ar-funnel-share').textContent = share.toFixed(1) + '%';
                row.querySelector('.ar-funnel-track').title = `${CHARTS.funnel.labels[i]}: ${format(value)} (${share.toFixed(1)}%)`;
                row.querySelector('.ar-funnel-track > div').style.width = Math.min(share, 100) + '%';
                row.querySelector('.ar-funnel-track > div').style.background = funnelColors[i];
            });
            const priorFields = filterEngine.previousYearTotals(FILTER_DATA, scope);
            document.querySelectorAll('[data-field-name]').forEach((card, i) => {
                const count = current[countFields[i]];
                const before = priorFields?.[countFields[i]];
                const change = before > 0 ? (count - before) / before * 100 : null;
                const percent = change === null ? null : `${change >= 0 ? '+' : ''}${change.toFixed(1)}%`;
                const changeLabel = percent === null ? 'N/A vs last AY' : `${percent} vs last AY`;
                card.dataset.fieldValue = count;
                card.dataset.fieldChange = percent ?? 'N/A';
                card.querySelector('strong').textContent = format(count);
                card.querySelector('small').textContent = changeLabel;
                card.querySelector('small').classList.toggle('text-body-secondary', change === null);
                card.querySelector('small').classList.toggle('text-danger', change !== null && change < 0);
                const tableCell = document.querySelectorAll('#fieldCountTable tbody tr')[i]?.querySelector('td');
                if (tableCell) tableCell.textContent = format(count);
                const percentCell = document.querySelectorAll('#fieldCountTable tbody tr')[i]?.querySelector('[data-field-percent]');
                if (percentCell) percentCell.textContent = percent ?? 'N/A';
            });
            const fieldHeading = document.querySelector('#fieldCountTable thead th:nth-child(2)');
            if (fieldHeading) fieldHeading.textContent = 'Selected scope';
            const departments = scope.department === 'All Departments'
                ? [...new Set(FILTER_DATA.rows.map(row => row.department))].sort() : [scope.department];
            Object.keys(COMPARISON).forEach(key => delete COMPARISON[key]);
            departments.forEach(department => {
                COMPARISON[department] = Object.fromEntries(years.map(year => [year, filterEngine.totals(filterEngine.select(FILTER_DATA, { ...scope, department }, year)).applicants]));
            });
            if (!departments.length) COMPARISON['No matching data'] = Object.fromEntries((years.length ? years : [scope.year]).map(year => [year, 0]));
            trendYears = [previousYear, scope.year].filter(Boolean);
            selectedYears = selectedYears.filter(year => years.includes(year));
            if (selectedYears.length < 2) selectedYears = years.slice(-2);
            const compareDepartment = document.getElementById('compareDepartment');
            const oldDepartment = compareDepartment.value;
            compareDepartment.replaceChildren(new Option('All Departments', ''), ...departments.map(name => new Option(name, name)));
            if (departments.includes(oldDepartment)) compareDepartment.value = oldDepartment;
            document.getElementById('yearPicker').replaceChildren(...years.map(year => new Option(year, year)));
            renderYearChips();
            drawTrends(document.querySelector('[data-chart-type="trends"]').value);
            drawPrograms();
            drawLocations(document.querySelector('[data-chart-type="locations"]').value);
            ['trends', 'programs', 'locations', 'funnel'].forEach(refreshTable);
            const comparisonWasShown = !document.getElementById('comparisonResult').classList.contains('d-none');
            if (comparisonWasShown && selectedYears.length >= 2) {
                refreshingComparison = true;
                document.getElementById('generateComparison').click();
                refreshingComparison = false;
            }
            else if (selectedYears.length < 2) document.getElementById('comparisonResult').classList.add('d-none');
            const projection = document.getElementById('filteredProjection');
            projection.replaceChildren(...departments.map(department => {
                const totalFor = year => filterEngine.totals(filterEngine.select(FILTER_DATA, { ...scope, department }, year || '')).enrolled;
                const now = totalFor(scope.year), before = totalFor(previousYear);
                const projected = before ? Math.max(0, now + now - before) : null;
                const column = document.createElement('div'); column.className = 'col';
                const card = document.createElement('div'); card.className = 'rounded-3 bg-body-tertiary border-top border-3 border-primary p-3 h-100';
                for (const [text, className] of [[department, 'small fw-bold text-body-secondary'], [`Selected AY: ${format(now)} enrolled`, 'small text-body-secondary'], [projected === null ? '—' : format(projected), 'fs-4 fw-bold'], [projected === null ? 'Prior-year enrollment unavailable' : 'Projected enrollment', 'small text-body-secondary']]) {
                    const line = document.createElement('div'); line.className = className; line.textContent = text; card.append(line);
                }
                column.append(card); return column;
            }));
            document.getElementById('analyticsFilterStatus').textContent = current.applicants ? `${format(current.applicants)} applicants match this scope.` : 'No applicants match these filters.';
            updateScope();
            const url = new URL(location.href);
            for (const [key, value] of Object.entries(scope)) url.searchParams.set(key, value);
            history.replaceState(null, '', url);
        }
        const initialScope = new URL(location.href).searchParams;
        for (const [key, id] of [['department', 'filterDepartment'], ['year', 'filterYear']]) {
            const select = document.getElementById(id), value = initialScope.get(key);
            if ([...select.options].some(option => option.value === value)) select.value = value;
        }
        const initialRange = [...document.querySelectorAll('input[name="range"]')].find(input => input.value === initialScope.get('range'));
        if (initialRange) initialRange.checked = true;
        document.querySelectorAll('input[name="range"], #filterDepartment, #filterYear').forEach(el => el.addEventListener('change', applyAnalyticsFilters));
        applyAnalyticsFilters();
