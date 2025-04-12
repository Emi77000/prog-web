let genresFilms = window.genresFilmsData || [];
let genresSeries = window.genresSeriesData || [];

function generatePieChart(ctx, data, labels) {
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: ['#ff5201','#ffc901','#00ca03','#00eec2','#005aee'],
            }]
        },
        options: {
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

document.addEventListener("DOMContentLoaded", function () {
    // Générer les labels et data
    const genresFilmsLabels = genresFilms.map(item => item.genre);
    const genresFilmsDataValues = genresFilms.map(item => item.count);
    const filmsCtx = document.getElementById('filmsChart').getContext('2d');
    generatePieChart(filmsCtx, genresFilmsDataValues, genresFilmsLabels);

    const genresSeriesLabels = genresSeries.map(item => item.genre);
    const genresSeriesDataValues = genresSeries.map(item => item.count);
    const seriesCtx = document.getElementById('seriesChart').getContext('2d');
    generatePieChart(seriesCtx, genresSeriesDataValues, genresSeriesLabels);
});
