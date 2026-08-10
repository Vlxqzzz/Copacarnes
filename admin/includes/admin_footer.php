    </div> <!-- Cierre .dashboard-container -->
</div> <!-- Cierre .admin-main -->

<script>
// Buscador en Vivo genérico para Tablas
document.addEventListener('DOMContentLoaded', function() {
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const tableId = this.getAttribute('data-table');
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#' + tableId + ' tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    });
});

// Función Global de Exportación (Simulación limpia)
function exportTableData(format, tableId) {
    alert('📥 Exportando reporte ' + tableId + ' en formato ' + format.toUpperCase() + '...\nEl archivo ha sido generado con éxito.');
}
</script>

</body>
</html>
