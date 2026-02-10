document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function() {
        const max = parseInt(this.getAttribute('data-max'));
        if (this.files.length > max) {
            alert("Можете да качите максимум " + max + " файла за тази секция.");
            this.value = "";
        }
    });
});

document.querySelectorAll('.file-item i.bx-trash').forEach(btn => {
    btn.addEventListener('click', async function() {
        if (!confirm("Сигурен ли си, че искаш да изтриеш този файл?")) return;
        const item = this.closest('.file-item');
        const filePath = item.getAttribute('data-path');

        const response = await fetch('delete_file.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${warrantyId}&file=${encodeURIComponent(filePath)}`
        });
        const result = await response.json();

        if (result.success) {
            item.remove();
        } else {
            alert("Грешка при изтриване на файла!");
        }
    });
});

const toggle = document.querySelector(".dark-toggle");

    toggle?.addEventListener("click", () => {
        document.body.classList.toggle("dark-theme");
        localStorage.setItem("theme",
            document.body.classList.contains("dark-theme") ? "dark" : "light"
        );
    });

if (localStorage.getItem("theme") === "dark") {
    document.body.classList.add("dark-theme");
}