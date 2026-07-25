// Dashboard Charts Setup
document.addEventListener("DOMContentLoaded", function() {
    
    // Check if Chart.js is loaded
    if (typeof Chart === "undefined") {
        console.warn("Chart.js is not loaded! Implementing CSS glassmorphic fallbacks.");

        // 1. Line Chart Fallback (draw a placeholder gradient path)
        const revenueWrapper = document.querySelector(".chart-area");
        if (revenueWrapper) {
            revenueWrapper.innerHTML = `
                <div class="chart-offline-fallback" style="height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; border-radius: 20px; background: rgba(124, 92, 255, 0.03); border: 1px dashed rgba(124, 92, 255, 0.2); padding: 20px;">
                    <div style="font-size: 40px; margin-bottom: 8px;">📈</div>
                    <h4 style="color: #7C5CFF; margin: 0 0 5px 0;">Revenue Overview (Offline)</h4>
                    <p style="color: #6B7280; font-size: 12px; margin: 0; text-align: center;">Daily Revenue active tracker is running. Connect to the internet to load full Chart.js graphics.</p>
                </div>
            `;
        }

        // 2. Donut Chart Fallback (Beautiful conic-gradient glass donut)
        const donutWrapper = document.querySelector(".donut-wrapper");
        if (donutWrapper) {
            donutWrapper.innerHTML = `
                <div class="css-donut-fallback" style="width: 120px; height: 120px; border-radius: 50%; background: conic-gradient(#7C5CFF 0% 45%, #2563EB 45% 70%, #16A34A 70% 85%, #F59E0B 85% 95%, #EC4899 95% 100%); position: relative; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 25px rgba(108,99,255,0.15); margin: auto;">
                    <div style="width: 78px; height: 78px; border-radius: 50%; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px; color: #4B5563; box-shadow: inset 0 2px 5px rgba(0,0,0,0.05);">
                        Services
                    </div>
                </div>
            `;
        }
        return; // Stop execution
    }

    // 1. Revenue Line Chart
    const revenueCtx = document.getElementById("revenueChart");
    if (revenueCtx) {
        const ctx = revenueCtx.getContext("2d");
        const gradient = ctx.createLinearGradient(0, 0, 0, 220);
        gradient.addColorStop(0, "rgba(124, 92, 255, 0.4)");
        gradient.addColorStop(1, "rgba(124, 92, 255, 0.0)");

        new Chart(revenueCtx, {
            type: "line",
            data: {
                labels: typeof revenueLabels !== "undefined" ? revenueLabels : ["1 Jun", "8 Jun", "15 Jun", "22 Jun", "30 Jun"],
                datasets: [{
                    label: "Revenue",
                    data: typeof revenueData !== "undefined" ? revenueData : [12000, 19000, 16000, 25000, 34000],
                    fill: true,
                    backgroundColor: gradient,
                    borderColor: "#7C5CFF",
                    borderWidth: 4,
                    tension: 0.4,
                    pointBackgroundColor: "#7C5CFF",
                    pointBorderColor: "#FFFFFF",
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 9
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: "rgba(17, 24, 39, 0.8)",
                        titleFont: { family: "Poppins", size: 13 },
                        bodyFont: { family: "Poppins", size: 12 },
                        padding: 12,
                        cornerRadius: 12,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return "₹ " + context.raw.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { family: "Poppins", size: 11, weight: "500" },
                            color: "#6B7280"
                        }
                    },
                    y: {
                        grid: {
                            color: "rgba(108, 99, 255, 0.06)",
                            drawBorder: false
                        },
                        ticks: {
                            font: { family: "Poppins", size: 11, weight: "500" },
                            color: "#6B7280",
                            callback: function(value) {
                                if (value >= 1000) {
                                    return "₹ " + (value / 1000) + "K";
                                }
                                return "₹ " + value;
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Service Donut Chart
    const serviceCtx = document.getElementById("serviceChart");
    if (serviceCtx) {
        new Chart(serviceCtx, {
            type: "doughnut",
            data: {
                labels: typeof serviceLabels !== "undefined" ? serviceLabels : ["General Service", "Repair Service", "Wash & Clean", "Tyre Service", "Others"],
                datasets: [{
                    data: typeof serviceCounts !== "undefined" ? serviceCounts : [45, 25, 15, 10, 5],
                    backgroundColor: [
                        "#7C5CFF", // Purple
                        "#2563EB", // Blue
                        "#16A34A", // Green
                        "#F59E0B", // Orange
                        "#EC4899"  // Pink
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "70%",
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: "rgba(17, 24, 39, 0.8)",
                        titleFont: { family: "Poppins", size: 13 },
                        bodyFont: { family: "Poppins", size: 12 },
                        padding: 12,
                        cornerRadius: 12
                    }
                }
            }
        });
    }
});