const themeToggle = document.getElementById('theme-toggle');
const body = document.body;
const icon = themeToggle ? themeToggle.querySelector('i') : null;

// Check for saved theme preference
const savedTheme = localStorage.getItem('theme');

// Apply saved theme or default to light
if (savedTheme === 'dark') {
    body.classList.add('dark-mode');
    if (icon) {
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
    }
}

// Toggle theme function
if (themeToggle) {
    themeToggle.addEventListener('click', () => {
        body.classList.toggle('dark-mode');
        
        if (body.classList.contains('dark-mode')) {
            localStorage.setItem('theme', 'dark');
            if (icon) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }
        } else {
            localStorage.setItem('theme', 'light');
            if (icon) {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        }
    });
}
