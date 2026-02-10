const sidebarToggleBtns = document.querySelectorAll(".sidebar-toggle");
const sidebar = document.querySelector(".sidebar");
const searchForm = document.querySelector(".search-form");
const themeToggleBtn = document.querySelector(".theme-toggle");
const themeIcon = themeToggleBtn.querySelector(".theme-icon");
const menuLinks = document.querySelectorAll(".menu-link");
const updateThemeIcon = () => {
  const isDark = document.body.classList.contains("dark-theme");
  themeIcon.textContent = sidebar.classList.contains("collapsed") ? (isDark ? "light_mode" : "dark_mode") : "dark_mode";
};

const savedTheme = localStorage.getItem("theme");
const systemPrefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
const shouldUseDarkTheme = savedTheme === "dark" || (!savedTheme && systemPrefersDark);
document.body.classList.toggle("dark-theme", shouldUseDarkTheme);
updateThemeIcon();

themeToggleBtn.addEventListener("click", () => {
  const isDark = document.body.classList.toggle("dark-theme");
  localStorage.setItem("theme", isDark ? "dark" : "light");
  updateThemeIcon();
});

sidebarToggleBtns.forEach((btn) => {
  btn.addEventListener("click", () => {
    sidebar.classList.toggle("collapsed");
	document.body.classList.toggle("sidebar-open", !sidebar.classList.contains("collapsed"));
    updateThemeIcon();
  });
});

searchForm.addEventListener("click", () => {
  if (sidebar.classList.contains("collapsed")) {
    sidebar.classList.remove("collapsed");
    searchForm.querySelector("input").focus();
  }
});

if (window.innerWidth > 768) sidebar.classList.remove("collapsed");


function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const icon    = document.querySelector('.toggle-btn i');
    sidebar.classList.toggle('hidden');
    icon.classList.toggle('bx-menu');
    icon.classList.toggle('bx-x');
}

function confirmDelete(e) {
    e.preventDefault();
    if (confirm("Сигурен ли си, че искаш да изтриеш профила си? Това ще премахне всички твои данни.")) {
        document.getElementById('deleteForm').submit();
    }
}


const monthsBg = [
    "януари", "февруари", "март", "април", "май", "юни",
    "юли", "август", "септември", "октомври", "ноември", "декември"
];

let currentDate = new Date();
let currentMonth = currentDate.getMonth(); 
let currentYear  = currentDate.getFullYear();

const daysContainer = document.getElementById('calendar-days');
const monthLabel    = document.getElementById('cal-month-label');
const tooltipEl     = document.getElementById('calendar-tooltip');

const byDate = {};
warrantiesData.forEach(w => {
    if (!byDate[w.date]) byDate[w.date] = [];
    byDate[w.date].push(w);
});

function pad(n) {
    return n < 10 ? '0' + n : '' + n;
}

function renderCalendar(year, month) {
    monthLabel.textContent = monthsBg[month] + " " + year;
    daysContainer.innerHTML = "";
    tooltipEl.textContent = "";

    const firstDayJs = new Date(year, month, 1).getDay();
    const firstDayIndex = (firstDayJs + 6) % 7; 

    const daysInMonth     = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth = new Date(year, month, 0).getDate();

    for (let i = 0; i < firstDayIndex; i++) {
        const d = daysInPrevMonth - firstDayIndex + 1 + i;
        const cell = document.createElement('div');
        cell.className = 'calendar-day outside';
        cell.innerHTML = '<div class="day-circle">' + d + '</div>';
        daysContainer.appendChild(cell);
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const cell = document.createElement('div');
        const dayCircle = document.createElement('div');
        dayCircle.className = 'day-circle';
        dayCircle.textContent = d;
        cell.className = 'calendar-day';

        const dateStr = year + "-" + pad(month + 1) + "-" + pad(d);
        const items = byDate[dateStr];

        if (items && items.length > 0) {
    cell.classList.add('has-expiry');

    const status = items[0].status;

    if (status === "ACTIVE") {
        dayCircle.classList.add("day-active");
    } else if (status === "EXPIRES_SOON") {
        dayCircle.classList.add("day-soon");
    } else if (status === "EXPIRED") {
        dayCircle.classList.add("day-expired");
    }

    cell.addEventListener('mouseenter', () => {
        const statusMap = {
			"ACTIVE": "Активна",
			"EXPIRES_SOON": "Изтича скоро",
			"EXPIRED": "Изтекла"
		};

		const lines = items.map(it =>
			it.product + " (" + (it.supplier || "неизвестен") + ") – " + (statusMap[it.status] || it.status)
		);


        tooltipEl.innerHTML =
            "<strong>" + dateStr + "</strong><br>" +
            lines.map(l => "• " + l).join("<br>");

        tooltipEl.classList.remove("tooltip-active", "tooltip-soon", "tooltip-expired");

        if (status === "ACTIVE") {
            tooltipEl.classList.add("tooltip-active");
        } else if (status === "EXPIRES_SOON") {
            tooltipEl.classList.add("tooltip-soon");
        } else {
            tooltipEl.classList.add("tooltip-expired");
        }

        tooltipEl.classList.add("active");
    });

    cell.addEventListener('mouseleave', () => {
        tooltipEl.classList.remove("active");
        tooltipEl.innerHTML = "";
    });
}


        cell.appendChild(dayCircle);
        daysContainer.appendChild(cell);
    }

    const totalCells = firstDayIndex + daysInMonth;
    const remaining = (7 - (totalCells % 7)) % 7;
    for (let i = 1; i <= remaining; i++) {
        const cell = document.createElement('div');
        cell.className = 'calendar-day outside';
        cell.innerHTML = '<div class="day-circle">' + i + '</div>';
        daysContainer.appendChild(cell);
    }
}

document.getElementById('cal-prev').addEventListener('click', () => {
    currentMonth--;
    if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    renderCalendar(currentYear, currentMonth);
});

document.getElementById('cal-next').addEventListener('click', () => {
    currentMonth++;
    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    renderCalendar(currentYear, currentMonth);
});

renderCalendar(currentYear, currentMonth);

document.getElementById('notifBell').addEventListener('click', function () {
    const box = document.getElementById('notifDropdown');
    box.classList.toggle('open');

    if (box.classList.contains('open')) {
        fetch('mark_notifications_read.php')
            .then(() => {
                const badge = document.querySelector('.notif-badge');
                if (badge) badge.remove();
            });
    }
});

document.querySelectorAll('.notif-delete').forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        
        const notifItem = this.closest('.notif-item');
        const id = notifItem.dataset.id;

        fetch('delete_notification.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id
        })
        .then(res => res.text())
        .then(data => {
            if (data === "OK") {
                notifItem.style.opacity = "0";
                notifItem.style.transform = "translateX(20px)";
                setTimeout(() => notifItem.remove(), 200);
            }
        });
    });
});

document.querySelectorAll('.notif-item').forEach(item => {
    item.addEventListener('click', function(e) {

        if (e.target.classList.contains('notif-delete')) return;

        const id = this.dataset.id;
        const elem = this;

        fetch('mark_single_notification_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id
        })
        .then(res => res.text())
        .then(data => {
            if (data === "OK") {
				
                elem.classList.remove('unread');
                elem.classList.add('read');

                const badge = document.querySelector('.notif-badge');
                if (badge) {
                    let num = parseInt(badge.innerText) - 1;
                    if (num <= 0) {
                        badge.remove();
                    } else {
                        badge.innerText = num;
                    }
                }
            }
        });
    });
});

document.addEventListener("click", function (e) {
    const dropdown = document.getElementById("notifDropdown");
    const bell = document.getElementById("notifBell");

    if (!dropdown.classList.contains("open")) return;

    if (dropdown.contains(e.target) || bell.contains(e.target)) return;

    dropdown.classList.remove("open");

    fetch("mark_notifications_read.php")
        .then(() => {
            const badge = document.querySelector(".notif-badge");
            if (badge) badge.remove();

            document.querySelectorAll(".notif-item.unread").forEach(item => {
                item.classList.remove("unread");
                item.classList.add("read");
            });
        });
});
