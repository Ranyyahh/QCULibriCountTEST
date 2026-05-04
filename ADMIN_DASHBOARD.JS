document.addEventListener("DOMContentLoaded", () => {
    // ============ NAVIGATION HIGHLIGHT ============
    // Get current page URL
    const currentPage = window.location.pathname.split('/').pop();
    
    // Find all navigation links
    const navLinks = document.querySelectorAll('.Nav-bar a');
    
    // Loop through links and add 'active' class to Dashboard link
    navLinks.forEach(link => {
        const linkPage = link.getAttribute('href');
        if (linkPage === 'ADMIN_DASHBOARD.html') {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
    
    const API_URL = 'get_real_time_data.php'; // Your PHP API file
    
    // Initial load
    fetchRealTimeData();
    
    // Update every 3 seconds (3000 milliseconds)
    setInterval(fetchRealTimeData, 3000);
    
    async function fetchRealTimeData() {
        try {
            const response = await fetch(API_URL + '?t=' + new Date().getTime());
            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            const data = await response.json();
            if (!data.success || !Array.isArray(data.activity)) {
                throw new Error(data.error || "Invalid dashboard response");
            }
            
            // Transform the API data to match your expected format
            const transformedData = {
                percent: Number(data.percentage) || 0,
                current: Number(data.current) || 0,
                max: Number(data.max) || 0,
                activity: data.activity.map(item => ({
                    type: item.type || "",
                    time: item.time || "",
                    date: item.date || ""
                }))
            };
            
    
            loadDashboard(transformedData);
            
        } catch (error) {
            console.log("Error fetching data, using fallback:", error);
            const now = new Date();
            const currentTime = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            const currentDate = now.toLocaleDateString();
          
            const placeholderData = {
                percent: 0,
                current: 0,
                max: 50,
                activity: Array.from({ length: 12 }, () => ({
                    type: "Connection Failed",
                    time: currentTime,
                    date: currentDate
                }))
            };
            
            loadDashboard(placeholderData);
        }
    }

    function loadDashboard(data) {
        const percent = data.max > 0 ? Math.round((data.current / data.max) * 100) : 0;
        document.getElementById("percent").textContent = percent + "%";
        document.getElementById("countNum").textContent = `${data.current}/${data.max}`;
        document.querySelector(".meter").style.strokeDasharray = `${percent}, 100`;

        updateStatus(percent);
        
        // Update recent activity
        loadActivity(data.activity);

        // Update date label with current time
        const now = new Date();
        document.getElementById("dateLabel").textContent = `[as of ${now.toLocaleDateString()} ${now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}]`;
    }

    function updateStatus(percent) {
        const status = document.getElementById("status");
        const meter = document.querySelector(".meter");
        const icon = document.querySelectorAll(".icon-person .head, .icon-person .body");
        const percentcolor = document.querySelector("#percent");

        let color = "";

        if (percent <= 50) {
            status.textContent = "NORMAL";
            color = "#4ca626";
        } else if (percent > 50 && percent < 90) {
            status.textContent = "NEAR FULL";
            color = "#f5bb13";
        } else {
            status.textContent = "FULL";
            color = "#b30000";
        }

        // Apply color
        status.style.color = color;
        meter.style.stroke = color;
        percentcolor.style.color = color;

        // Apply to both head and body
        icon.forEach(part => part.style.background = color);
    }

    function loadActivity(activity) {
        const list = document.getElementById("activityList");
        list.innerHTML = ""; // clear first

        activity.forEach(item => {
            const box = document.createElement("div");
            box.classList.add("activity-item");

            if (item.type.includes("-")) box.classList.add("minus");

            const type = document.createElement("div");
            type.textContent = item.type;

            const time = document.createElement("div");
            time.textContent = item.time;

            const date = document.createElement("div");
            date.textContent = item.date;

            box.appendChild(type);
            box.appendChild(time);
            box.appendChild(date);
            list.appendChild(box);
        });
    }
});
