// Обработка кнопок периода
document.querySelectorAll('.period-btn').forEach(button => {
    button.addEventListener('click', function() {
        this.parentElement.querySelectorAll('.period-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        this.classList.add('active');
    });
});

// Ждем загрузки DOM перед созданием графика
document.addEventListener('DOMContentLoaded', function() {
    // Получаем контекст canvas элемента
    const ctx = document.getElementById('workHoursChart').getContext('2d');

    // Создаем график
    const workHoursChart = new Chart(ctx, {
        type: 'bar',
        data: {
            // Месяцы по оси X
            labels: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь'],

            // 4 набора данных - по одному для каждого отдела
            datasets: [
                {
                    label: 'Разработка',
                    data: [8.5, 8.7, 8.2, 8.8, 8.3, 8.1],
                    backgroundColor: 'rgba(44, 107, 255, 0.8)',
                    borderColor: 'rgba(44, 107, 255, 1)',
                    borderWidth: 1,
                    barPercentage: 0.8,
                    categoryPercentage: 0.9
                },
                {
                    label: 'Продажи',
                    data: [7.8, 8.0, 7.5, 8.2, 7.7, 7.9],
                    backgroundColor: 'rgba(247, 144, 9, 0.8)',
                    borderColor: 'rgba(247, 144, 9, 1)',
                    borderWidth: 1,
                    barPercentage: 0.8,
                    categoryPercentage: 0.9
                },
                {
                    label: 'Маркетинг',
                    data: [7.5, 7.8, 7.2, 7.9, 7.4, 7.7],
                    backgroundColor: 'rgba(18, 183, 106, 0.8)',
                    borderColor: 'rgba(18, 183, 106, 1)',
                    borderWidth: 1,
                    barPercentage: 0.8,
                    categoryPercentage: 0.9
                },
                {
                    label: 'HR',
                    data: [7.0, 7.3, 6.8, 7.5, 7.1, 7.2],
                    backgroundColor: 'rgba(240, 68, 56, 0.8)',
                    borderColor: 'rgba(240, 68, 56, 1)',
                    borderWidth: 1,
                    barPercentage: 0.8,
                    categoryPercentage: 0.9
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.parsed.y}ч`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    min: 6,
                    max: 9,
                    ticks: {
                        callback: function(value) {
                            return value + 'ч';
                        },
                        stepSize: 0.5,
                        font: {
                            size: 11
                        }
                    },
                    title: {
                        display: true,
                        text: 'Часы работы в день',
                        font: {
                            size: 12,
                            weight: 'bold'
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        font: {
                            size: 12,
                            weight: 'bold'
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
});
