function showSelectedFiles(input, listId) {
    const fileList = document.getElementById(listId);
    fileList.innerHTML = "";
    for (const file of input.files) {
        const li = document.createElement('div');
        li.textContent = "📄 " + file.name;
        fileList.appendChild(li);
    }
}

function validateFileLimit(input) {
    const max = parseInt(input.getAttribute('data-max'));
    if (input.files.length > max) {
        alert("Можете да качите максимум " + max + " файла за тази секция.");
        input.value = ""; 
    }
}

document.querySelector('input[name="receipt_files[]"]').addEventListener('change', function() {
    validateFileLimit(this);
    showSelectedFiles(this, 'fileList');
});

document.querySelector('input[name="warranty_files[]"]').addEventListener('change', function() {
    validateFileLimit(this);
    showSelectedFiles(this, 'warrantyFileList');
});

if (localStorage.getItem("theme") === "dark") {
        document.body.classList.add("dark-theme");
}