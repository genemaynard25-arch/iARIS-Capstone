<script>
document.addEventListener('DOMContentLoaded', () => {
    window.onScholarDrawer = scholar => {
        const box = document.getElementById('lampAsk');
        box.replaceChildren();
        const link = document.createElement('a');
        link.className = 'btn btn-outline-primary mt-3';
        link.href = @json(route('lamp-requests.index')) + '?applicant=' + encodeURIComponent(scholar.id);
        link.textContent = 'Open LAMP conversation';
        box.appendChild(link);
    };
    const counts = document.getElementById('lampThreadCounts');
    if (counts) counts.textContent = @json(\App\Support\LampRequests::waitingCount()) + ' waiting for LAMP.';
});
</script>
