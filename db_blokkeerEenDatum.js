//import
import { resetDayStyles, applyDayStyles, getISODate } from './db_module_StyleCalender'

//Flatpick initialiseren
flatpickr.localize(flatpickr.l10ns.nl);

/** || ------------- constants ------------ */ 
const calenderData = window.calenderData || [];
const generalMessage = document.getElementById('receive-message-general') || null;
const calenderMessage = document.getElementById('receive-message-calender') || null;
const calenderSubmitButtonMessage = document.getElementById('receive-message-calenderSubmitKnop') || null;
const noAbsolutePositionIDSArray = ['receive-message-calender', 'receive-message-general'];



/** || ---------------functies------------------------||  */
//bericht voor een duur laten zien en naar toe scrollen
function showFlashMessage(flashMessage, duration = 5000, text = '') {
    if (!flashMessage) return;
    const elementId = flashMessage.id;
    if(!elementId) return;

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


function initFlatpickr(unavailableDates = []) {
    const currentDateSQL = getISODate(new Date())
    const currentDate = new Date();
    const endOfYearDate = getISODate(new Date(currentDate.getFullYear(), 11, 31));  // 31 december van dit jaar
    if (Array.isArray(unavailableDates) && unavailableDates.length){
        unavailableDates.map( date =>
            getISODate(new Date(date.bezoekdatum))
        )
    }
    let unavailableDatesArray = []
    unavailableDates.forEach(datum =>
        unavailableDatesArray.push(datum.bezoekdatum)
    )


    const schoolVacations = [
        { start: '2025-01-01', end: '2025-01-05'},
        { start: '2025-02-15', end: '2025-03-09' },  // Voorjaarsvakantie 2025
        { start: '2025-04-18', end: '2025-05-04' },  // Meivakantie 2025
        { start: '2025-05-29', end: '2025-06-01' },  // Meivakantie 2025
        { start: '2025-06-07', end: '2025-06-09' },  // Meivakantie 2025
        { start: '2025-07-05', end: '2025-08-31' },  // Zomervakantie 2025
        { start: '2025-10-12', end: '2025-10-26' },  // Herfstvakantie 2025
        { start: '2025-12-21', end: '2025-12-31' }   // Kerstvakantie 2025
    ];

    function isVacation(date) {
        return schoolVacations.some(vacation => {
            const start = vacation.start;
            const end = vacation.end;
            return date >= start && date <= end;
        });
    }

    flatpickr("#start_date", {
        locale: "nl",
        dateFormat: "d F Y",
        weekNumbers: true,
        enableTime: false,
        disable: [
            function (date) {
                const SQLdate = getISODate(new Date(date));
                const isWeekend = (date.getDay() === 0 || date.getDay() === 6);
                const isSchoolVacation = isVacation(SQLdate);
                const isPastDate = SQLdate < currentDateSQL; // Datum uit het verleden uitschakelen
                const isNextYear = SQLdate > endOfYearDate; // Datum na dit jaar uitschakelen
                const isUnavailable = unavailableDatesArray.includes(SQLdate); // Correcte controle
                return isWeekend || isSchoolVacation || isPastDate || isNextYear || isUnavailable;
            }
        ],
        onDayCreate: function (dObj, dStr, fp, dayElem) {
            
            resetDayStyles(dayElem);
            applyDayStyles(dayElem, '', 'blokkeer');

            dayElem.addEventListener('mouseenter', () => {
                dayElem.classList.add('hoverOverCalenderDate');
            });

            dayElem.addEventListener('mouseleave', () => {
                dayElem.classList.remove('hoverOverCalenderDate');
            });

            if (dayElem.classList.contains('selected')) {
                dayElem.classList.add('calendar-day.Selected.extraLayer');
            }
            
            if (dayElem.classList.contains('flatpickr-disabled')) {
                dayElem.style.opacity = '0'; // Verlaag de opacity
            }
        },
        firstDayOfWeek: 1
    });
}



/** || ----------------- script logica (functies en constanten in gebruik) ---------------||| */


//calender in laden met data
initFlatpickr(calenderData);



if (generalMessage){
    showFlashMessage(generalMessage)
}
if (calenderMessage){
    showFlashMessage(calenderMessage)
}
if (calenderSubmitButtonMessage){
    showFlashMessage(calenderSubmitButtonMessage)
}

const toggle = document.querySelector(".meerInformatieToggle");

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
        if (targetId === "datumvastzettenInfo") {
            toggle.querySelector("span").textContent = "Meer informatie datum blokkeren";
        } 
    }
});



