function startTimer(duration, sessionId) {
    let time = duration;
    const timerDisplay = document.getElementById('timer');
    const completeBtn = document.getElementById('completeBtn');

    const interval = setInterval(() => {
        const minutes = Math.floor(time / 60);
        const seconds = time % 60;
        timerDisplay.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;

        if (time <= 0) {
            clearInterval(interval);
            timerDisplay.textContent = "0:00";
            completeBtn.disabled = false;
        } else {
            time--;
        }
    }, 1000);
}

console.log("HabitMate JS loaded");