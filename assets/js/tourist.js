document.addEventListener("DOMContentLoaded", () => {
    const searchBtn = document.getElementById("searchBtn");
    const destSelect = document.getElementById("filterDestination");
    const budgetSelect = document.getElementById("filterBudget");
    const tableRows = document.querySelectorAll("#popularToursTable tbody tr");

    if (searchBtn) {
        searchBtn.addEventListener("click", () => {
            const selectedDest = destSelect.value;
            const selectedBudget = budgetSelect.value;

            tableRows.forEach(row => {
                const rowDest = row.getAttribute("data-destination");
                const rowPrice = parseFloat(row.getAttribute("data-price"));

                let matchesDest = (selectedDest === "ALL" || rowDest.includes(selectedDest));
                let matchesBudget = (selectedBudget === "ALL" || rowPrice <= parseFloat(selectedBudget));

                if (matchesDest && matchesBudget) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });
    }
});