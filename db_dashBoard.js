//imports
import { resetDayStyles, applyDayStyles } from './db_module_StyleCalender';


flatpickr.localize(flatpickr.l10ns.nl);

/** || ------------- constants ------------ */ 

//kalenderdata en globale window variabele 
const weekDataResponse = window.weekDataResponse || {};
const weekData = weekDataResponse.weekdata || [];

//ontvang divs voor de bericht server
const weekKalenderMessage = document.getElementById('receive-message-weekkalender');
const flashMessageWeekaanvraag = document.getElementById('receive-message-WeekAanvraag');
const flashMessageInOptie = document.getElementById('receive-message-InOptie');
const flashMessageGeneral = document.getElementById('receive-message-general');
const changeStatusMessage = document.getElementById('change-status-message') ?? null;
const noAbsolutePositionIDSArray = ['receive-message-InOptie', 'receive-message-general'];
const hash = window.location.hash ?? '';


/**--------------[functies]--------------------------------  */

function showFlashMessage(flashMessage, duration = 5000) {
    if (!flashMessage) return;
    const elementId = flashMessage.id;
    if (!elementId) return;
    if (flashMessage && flashMessage.textContent.trim() !== '') {   
        if (!noAbsolutePositionIDSArray.includes(elementId)){
            flashMessage.style.display = 'flex';
        } else {
            flashMessage.style.display = 'flex';
            flashMessage.classList.add('flash-message-alternative');
        }
        if (elementId !== 'receive-message-general') {
            setTimeout(() => {
                flashMessage.classList.add('fade-out'); // Start fade-out effect
                setTimeout(() => {
                    flashMessage.style.display = 'none'; // Verberg na de fade
                    flashMessage.classList.remove('fade-out'); // Reset voor hergebruik
                    if (elementId === 'receive-message-InOptie'){
                        flashMessage.classList.remove('flash-message-alternative'); // Reset voor hergebruik
                    }
                }, 1000); // Tijd overeenkomend met de CSS-transitie
            }, duration);
        }
    }
}



function initFlatpickr(agendaData = null) {
    let uniqueWeeks;
    agendaData !== null ? uniqueWeeks = new Set(agendaData.map(item => `${item.jaar}-${item.weeknummer}`)) : uniqueWeeks = null;
    flatpickr("#start_date", {
        locale: "nl",  // Stel in op Nederlands
        weekNumbers: true,  // Toon weeknummers
        dateFormat: "d F Y",  // Dag, maand, jaar
        disable: [
            function (date) {
                const weekNumber = getWeekNumber(date);  // Bereken weeknummer
                const year = date.getFullYear();  // Haal het jaar op

                // Controleer zowel het weeknummer als het jaar
                const isValidDate = uniqueWeeks.has(`${year}-${weekNumber}`);
                
                // Log data voor debugging
        

                return !isValidDate || date.getDay() === 0 || date.getDay() === 6;
            }
        ],
        onDayCreate: function (dObj, dStr, fp, dayElem) {
            const weekNumber = getWeekNumber(dayElem.dateObj);
            const year = dayElem.dateObj.getFullYear();
            const dayOfWeek = dayElem.dateObj.getDay();
            const isValidDate = uniqueWeeks.has(`${year}-${weekNumber}`);
        
            resetDayStyles(dayElem);  // Reset stijlen
       

        
            if (dayElem.classList.contains('selected')) {
                dayElem.classList.add('calendar-day.Selected.extraLayer');
            }
        
            // Gebruik nu de juiste controle met jaar en weeknummer
            if (isValidDate) {
                if (dayOfWeek === 1) {
                    // Maandagen groen en enabled
                    applyDayStyles(dayElem, 'maandag', 'weekaanvragen');
                } else if (dayOfWeek > 1 && dayOfWeek < 6) {
                    // Andere weekdagen blauw en disabled
                    applyDayStyles(dayElem, '', 'weekaanvragen');
                    dayElem.classList.add('flatpickr-disabled');
                }
            }
        
            if (!dayElem.classList.contains('flatpickr-disabled')) {
                // Hover effect
                dayElem.addEventListener('mouseenter', () => {
                    dayElem.classList.add('hoverOverCalenderDate');
                });
        
                dayElem.addEventListener('mouseleave', () => {
                    dayElem.classList.remove('hoverOverCalenderDate');
                });
    
            }

        },
        firstDayOfWeek: 1
    });
}



// Functie om weeknummer te berekenen (ISO-week)
function getWeekNumber(date) {
    const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
    const dayNum = d.getUTCDay() || 7;
    d.setUTCDate(d.getUTCDate() + 4 - dayNum);
    const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
}


// weekkalender inladen met data

if (weekData.length > 0) {
    initFlatpickr(weekData);
}else {
    initFlatpickr();
}

/**---------[berichten opvangen]------------ */

if (weekKalenderMessage){
    showFlashMessage(weekKalenderMessage);
}

if (flashMessageWeekaanvraag){
    showFlashMessage(flashMessageWeekaanvraag)
}

if (changeStatusMessage) {
    showFlashMessage(changeStatusMessage);
}

if(flashMessageInOptie){
    showFlashMessage(flashMessageInOptie);
}

if (flashMessageGeneral) {
    showFlashMessage(flashMessageGeneral);
}


    
// meer info toggles
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
            if (targetId === "aanvragenInfo") {
                toggle.querySelector("span").textContent = "Meer informatie over het bekijken van de aanvragen";
            } else if (targetId =="aanvrageninoptieInfo"){
                toggle.querySelector("span").textContent = "Meer informatie over het bekijken van de aanvragen in optie";
            }
        }
    });
});



if (hash && hash !=='') {
    const targetElement = document.querySelector(`${hash}`);
    if (targetElement) {
    
        targetElement.scrollIntoView({ behavior: 'instant', block:'start' });
        if (hash === '#change-status-message'){
            setTimeout(() => {
                window.scrollBy({ top: -200, behavior: 'smooth' });
            }, 150); // Wacht totdat de animatie van scrollIntoView klaar is
        }

    }
}




