<script>
    function extractSchoolName(label) {
        var raw = (label || '').trim();
        return raw.replace(/^[A-Z0-9\/-]+\s*-\s*/i, '').trim();
    }

    function applyFilters() {
        var input = document.getElementById('schoolSearch');
        var textFilter = input ? input.value.toLowerCase() : '';

        var activeAlpha = document.querySelector('.alpha-link.active');
        var letter = activeAlpha ? activeAlpha.getAttribute('data-letter') : 'ALL';

        var container = document.getElementById('schoolsContainer');
        if (!container) return;

        var items = container.getElementsByClassName('item');
        var anyVisible = false;

        for (var i = 0; i < items.length; i++) {
            var txtValue = (items[i].getAttribute('data-label') || '').trim();
            var txtLower = txtValue.toLowerCase();
            var schoolName = extractSchoolName(txtValue);
            var schoolNameLower = schoolName.toLowerCase();

            var matchesText = !textFilter || txtLower.indexOf(textFilter) > -1 || schoolNameLower.indexOf(textFilter) > -1;

            var matchesLetter = true;
            if (letter && letter !== 'ALL') {
                var firstChar = schoolName.charAt(0).toUpperCase();
                matchesLetter = firstChar === letter;
            }

            if (matchesText && matchesLetter) {
                items[i].style.display = '';
                anyVisible = true;
            } else {
                items[i].style.display = 'none';
            }
        }

        var noResults = document.getElementById('noResults');
        if (noResults) noResults.style.display = anyVisible ? 'none' : 'block';
    }

    document.addEventListener('DOMContentLoaded', function () {
        var alpha = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
        var wrap = document.getElementById('alphaLetters');
        if (wrap) {
            alpha.forEach(function (ch) {
                var btn = document.createElement('button');
                btn.className = 'alpha-link';
                btn.setAttribute('data-letter', ch);
                btn.textContent = ch;
                wrap.appendChild(btn);
            });
        }

        var input = document.getElementById('schoolSearch');
        if (input) {
            input.addEventListener('keyup', function (event) {
                if (event.key === 'Enter') event.preventDefault();
                applyFilters();
            });
        }

        var alphaButtons = document.querySelectorAll('.alpha-link');
        alphaButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                alphaButtons.forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                applyFilters();
            });
        });
    });
</script>
