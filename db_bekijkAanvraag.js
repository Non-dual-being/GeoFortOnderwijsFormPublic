//imports
import { applyDayStyles, resetDayStyles, getISODate } from './db_module_StyleCalender';


//Flatpick initialiseren
flatpickr.localize(flatpickr.l10ns.nl);

/** || ------------- constants ------------ */ 
const calenderData = window.calenderData || [];
const generalMessage = document.getElementById('receive-message-general') || null;
const calenderMessage = document.getElementById('receive-message-calender') || null;
const calenderSubmitButtonMessage = document.getElementById('receive-message-calenderSubmitKnop') || null;
const generalMessageRequests = document.getElementById('receive-message-general-Requests') || null;
const noAbsolutePositionIDSArray = ['receive-message-calender', 'receive-message-general', 'downloadRequests-button-message'];
const downLoadRequestsButtonMessage = document.getElementById('downloadRequests-button-message');
const downLoadMessage = document.getElementById('download-button-message');
const downloadButton = document.getElementById("download-button") || null;





/** || ---------------functies------------------------||  */

//bericht voor een duur laten zien en naar toe scrollen
function showFlashMessage(flashMessage, duration = 5000, text = '') {
    if (!flashMessage) return;
    const elementId = flashMessage.id;

    if (text !==''){
        flashMessage.textContent = String(text.trim()).replace(/[";]+/g, '');
    }
    if (flashMessage && flashMessage.textContent.trim() !== '') {
        if (text !== ''){
            flashMessage.style.display = 'flex';
        } else {
            flashMessage.style.display = 'flex';
            if (!noAbsolutePositionIDSArray.includes(elementId)) {
                if (elementId !== 'change-number-message') {
                flashMessage.classList.add('absolute-positioned-General');
                } 
            } else {
                if (elementId !== 'downloadRequests-button-message'){
                    flashMessage.classList.add('flash-message-alternative');
                }
            }
        }
        flashMessage.scrollIntoView({behavior:"smooth", block: "center"});
        if (elementId !== 'receive-message-general'){
            setTimeout(() => {
                flashMessage.classList.add('fade-out'); // Start fade-out effect
                setTimeout(() => {
                    flashMessage.style.display = 'none'; // Verberg na de fade
                    flashMessage.classList.remove('fade-out'); // Reset voor hergebruik
                }, 1000); // Tijd overeenkomend met de CSS-transitie
            }, duration);
        }
    }
}


//agenda in laden
function initFlatpickr(datesWithRequests = []) {
    const dateMap = new Map();
    if (Array.isArray(datesWithRequests) && datesWithRequests.length) {
        datesWithRequests.forEach(item => {
            const dateKey = new Date (item.bezoekdatum);
            const dateKeySQL = getISODate(dateKey);
            dateMap.set(dateKeySQL, item.aantal_scholen)
        });
    }

    flatpickr("#start_date", {
        locale: "nl",
        dateFormat: "d F Y",
        weekNumbers: true,
        enableTime: false,
        appendTo: document.body,
        disable: [
            function(date) {
                const isoDate = getISODate(date);
                return !dateMap.has(isoDate);
            }
        ],
    onDayCreate: function (dObj, dStr, fp, dayElem) {
        const isoDate = getISODate(dayElem.dateObj);
        const aantalScholen = dateMap.get(isoDate);

        resetDayStyles(dayElem);


        if (aantalScholen) {
            applyDayStyles(dayElem, aantalScholen, 'bekijkaanvraag');
            dayElem.addEventListener('mouseenter', () => {
                dayElem.classList.add('hoverOverCalenderDate');
            });
            dayElem.addEventListener('mouseleave', () => {
                dayElem.classList.remove('hoverOverCalenderDate');
            });
        }

        
        if (dayElem.classList.contains('selected')) {
            dayElem.classList.add('calendar-day.Selected.extraLayer');
        }
        
    },
    firstDayOfWeek: 1
    });
}



/** || ----------------- script logica (functies en constanten in gebruik) ---------------||| */


//calender in laden met data
initFlatpickr(calenderData);

//(fout)berichten laten zien
if (generalMessage){
    showFlashMessage(generalMessage);
}

if (calenderMessage){
    showFlashMessage(calenderMessage);
}

if (calenderSubmitButtonMessage) {
    showFlashMessage(calenderSubmitButtonMessage);
}

if (generalMessageRequests){
    showFlashMessage(generalMessageRequests);
}
if (downLoadRequestsButtonMessage){
    showFlashMessage(downLoadRequestsButtonMessage);
}

if (downLoadMessage){
    showFlashMessage(downLoadMessage);
}

//dit is het basencode downloadcsv wat we mogelijk gaan omzetten naar een hidden form




const toggles = document.querySelectorAll(".meerInformatieToggle");

toggles.forEach(toggle => {
toggle.addEventListener("click", function(event) {
    event.preventDefault(); // Voorkom dat de pagina scrollt naar de top
    
    // Haal het doel-element op via de data-target attribuut
    const targetId = toggle.getAttribute("data-target");
    const targetContent = document.getElementById(targetId);
    
    // Toggle de "open" class om de inhoud te tonen of verbergen
    targetContent.classList.toggle("open");

    // Pas de tekst van de toggle-link aan
    if (targetContent.classList.contains("open")) {
        toggle.querySelector("span").textContent = "Verberg ";
    } else {
        // Voeg hier de check toe voor bezoektijdenInfo
        if (targetId === "dagaanvragenOverzicht") {
            toggle.querySelector("span").textContent = "Meer informatie aanvraagoverzicht";
        } 
    }
});
});

document.addEventListener('change', event => {
if (event.target.matches('input[id^="downloadCheck-"]')){
    const checkBox = event.target;
    const parent = checkBox.parentElement;
    const label = parent.querySelector('label');

    label.classList.toggle('checkedForDownload', checkBox.checked);
    label.classList.toggle('extraClass', checkBox.checked);
    parent.classList.toggle('Checked', checkBox.checked);
}
})




if (downloadButton){
downloadButton.addEventListener("click", function(){
    const checkDownload = setInterval(
        function(){
            if (document.cookie.includes("download_started=1")){
                clearInterval(checkDownload);
                const flashMessage = document.getElementById("donwload-started-message");
                if (flashMessage){
                    showFlashMessage("donwload-started-message", 5000, "bestanden zijn gedownload");
                }
                document.cookie = "download_started=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            }
        }, 500)
})
}



/**setInterval ontvangt een functie en een tijdsperiode, de functie herhaalt zich elke interval tot dat het intervals stopt */


