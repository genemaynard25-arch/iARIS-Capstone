/* A bounded page list shared by the applicant pipeline and record tables. */
window.IARIS_PAGINATION = {
    pages(current, total) {
        total = Math.max(1, Math.trunc(Number(total) || 1));
        current = Math.max(1, Math.min(total, Math.trunc(Number(current) || 1)));
        if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
        const pages = new Set([1, total, current - 1, current, current + 1]);
        if (current <= 3) [2, 3, 4].forEach(page => pages.add(page));
        if (current >= total - 2) [total - 3, total - 2, total - 1].forEach(page => pages.add(page));
        const result = [];
        [...pages].filter(page => page >= 1 && page <= total).sort((a, b) => a - b).forEach(page => {
            const previous = result[result.length - 1];
            if (page - previous === 2) result.push(previous + 1);
            else if (page - previous > 2) result.push(null);
            result.push(page);
        });
        return result;
    },
};
