function confirmDelete(e) {
	e.preventDefault();
	if (confirm("Сигурен ли си, че искаш да изтриеш профила си? Това ще премахне всички твои данни.")) {
	  document.getElementById('deleteForm').submit();
	}
  }
	  
  let inBGN = true;
const rate = 1.95583;

document.getElementById("currency-toggle").addEventListener("click", function(e) {
	e.preventDefault();

	const priceCells = document.querySelectorAll(".price-cell");

	priceCells.forEach(td => {
		let value = parseFloat(td.dataset.raw || "0");

		if (!inBGN) {
			td.innerText = (value).toFixed(2) + " лв.";
		} else {
			td.innerText = (value / rate).toFixed(2) + " €";
		}
	});

	this.innerHTML = inBGN 
		? "<i class='bx bx-transfer'></i> BGN"
		: "<i class='bx bx-transfer'></i> EUR";

	inBGN = !inBGN;
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