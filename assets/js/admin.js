/**
 * Image Frame Generator — Admin Dashboard Scripts
 */
$(document).ready(function() {
    
    // Initialize Dashboard Chart if element exists
    const chartCtx = document.getElementById('generationChart');
    if (chartCtx && typeof chartLabels !== 'undefined' && typeof chartData !== 'undefined') {
        new Chart(chartCtx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Images Generated',
                    data: chartData,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#6366f1'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: '#94a3b8' },
                        grid: { color: 'rgba(255,255,255,0.05)' }
                    },
                    x: {
                        ticks: { color: '#94a3b8' },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // Auto-dismiss alerts after 4 seconds
    setTimeout(function() {
        $('.alert-dismissible').fadeOut('slow', function() { $(this).remove(); });
    }, 4000);
});
