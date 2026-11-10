<script>
        // ---- Password: strength meter, and check the two new passwords match before sending ----
        const newPassword = document.getElementById('newPassword');
        newPassword?.addEventListener('input', () => {
            const v = newPassword.value;
            let score = 0;
            if (v.length >= 8) score++;
            if (v.length >= 12) score++;
            if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
            if (/\d/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;
            score = v ? Math.max(1, Math.min(score, 4)) : 0;
            const [label, tone] = [['—', ''], ['Weak', 'danger'], ['Fair', 'warning'], ['Good', 'info'], ['Strong', 'primary']][score];
            const bar = document.getElementById('strengthBar');
            bar.style.width = score * 25 + '%';
            bar.className = `progress-bar ${tone ? 'bg-' + tone : ''}`;
            document.getElementById('strengthText').textContent = 'Strength: ' + label;
        });
        document.getElementById('passwordForm')?.addEventListener('submit', e => {
            const current = document.getElementById('currentPassword');
            const confirm = document.getElementById('confirmPassword');
            const checks = [
                [current, !current.value],
                [newPassword, !(newPassword.value.length >= 8 && /[A-Za-z]/.test(newPassword.value) && /\d/.test(newPassword.value))],
                [confirm, !confirm.value || confirm.value !== newPassword.value],
            ];
            checks.forEach(([field, bad]) => field.classList.toggle('is-invalid', bad));
            if (checks.some(([, bad]) => bad)) e.preventDefault();
        });
</script>
