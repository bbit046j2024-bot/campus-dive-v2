document.addEventListener('DOMContentLoaded', () => {
    const evtSource = new EventSource('notifications.php');

    evtSource.onmessage = function (event) {
        const data = JSON.parse(event.data);
        updateNotificationBadges(data);
    };

    evtSource.onerror = function (err) {
        // console.error("EventSource failed:", err);
        // connection might be closed or re-establishing
    };

    function updateNotificationBadges(data) {
        // Update Message Badges
        const badges = document.querySelectorAll('.badge'); // Select all badges
        const navBadge = document.querySelector('.sidebar-link[href*="messages"] .badge');

        if (data.unread_messages > 0) {
            // Update existing or create new
            if (navBadge) {
                navBadge.innerText = data.unread_messages;
                navBadge.style.display = 'inline-block';
            }

            // Should also update top header bell if exists
            const bellBadge = document.getElementById('bellBadge');
            if (bellBadge) {
                bellBadge.innerText = data.unread_messages;
                bellBadge.style.display = 'block';
            }
        } else {
            if (navBadge) navBadge.style.display = 'none';
            const bellBadge = document.getElementById('bellBadge');
            if (bellBadge) bellBadge.style.display = 'none';
        }
    }
});
