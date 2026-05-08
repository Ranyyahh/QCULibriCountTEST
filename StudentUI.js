/* =======================================
   DYNAMIC GAUGE + STATUS FUNCTION
   ======================================= */
function updateGauge(percent, current, max) {

    // Update % text
    document.querySelector(".percent").textContent = percent + "%";

    // Update numeric count
    document.querySelector(".count").textContent = `${current}/${max}`;

    // Update gauge arc
    document.querySelector(".meter").style.strokeDasharray = `${percent}, 100`;

    // Select elements
    let statusText = document.querySelector(".status");
    let meterArc = document.querySelector(".meter");
    let percentText = document.querySelector(".percent");
    let head = document.querySelector(".icon-person .head");
    let body = document.querySelector(".icon-person .body");

    let color = "";

    /* STATUS LOGIC */
    if (percent <= 50) {
        statusText.textContent = "NORMAL";
        color = "#7da37d";  // green
    }
    else if (percent > 50 && percent < 90) {
        statusText.textContent = "NEAR FULL";
        color = "#eec857";  // yellow
    }
    else {
        statusText.textContent = "FULL";
        color = "#ca5353";  // red
    }

    // Apply color to all elements
    statusText.style.color = color;
    meterArc.style.stroke = color;
    percentText.style.color = color;

    // Person icon color
    head.style.background = color;
    body.style.background = color;
}

/* =======================================
   DATE/TIME UPDATER
   ======================================= */
function updateDateTime() {
    const now = new Date();

    const options = {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        hour12: true
    };

    const formatted = now.toLocaleString("en-US", options);

    document.getElementById("datetime").textContent = `As of ${formatted}`;
}

// Update immediately and every second
updateDateTime();
setInterval(updateDateTime, 1000);

/* =======================================
   REAL-TIME CROWD DATA
   ======================================= */

async function fetchCrowdData() {
    try {
        const response = await fetch('get_real_time_data.php?t=' + new Date().getTime());
        const data = await response.json();

        if (data.success) {
            const current = data.current;
            const max = data.max;
            const percent = Math.round((current / max) * 100);

            updateGauge(percent, current, max);
        } else {
            console.error("API error:", data);
        }
    } catch (err) {
        console.error("Failed to fetch real-time data:", err);
    }
}

// Initial fetch
fetchCrowdData();

// Update every 3 seconds
setInterval(fetchCrowdData, 3000);
