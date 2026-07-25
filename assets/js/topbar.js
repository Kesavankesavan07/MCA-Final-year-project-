function updateDateTime(){

    const now = new Date();

    const dateOptions = {
        weekday:'long',
        day:'numeric',
        month:'long',
        year:'numeric'
    };

    document.getElementById("currentDate").innerHTML =
        now.toLocaleDateString('en-IN', dateOptions);

    document.getElementById("currentTime").innerHTML =
        now.toLocaleTimeString();
}

setInterval(updateDateTime,1000);

updateDateTime();

const themeBtn = document.getElementById("themeToggle");

themeBtn.addEventListener("click",()=>{

    document.body.classList.toggle("dark-mode");

});