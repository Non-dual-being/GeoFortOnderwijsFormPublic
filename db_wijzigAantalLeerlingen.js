import { applyDayStyles, resetDayStyles, getISODate } from "./db_module_StyleCalender";


//Flatpick initialiseren
flatpickr.localize(flatpickr.l10ns.nl);

/** || ------------- constants ------------ */ 

const InOptieKalenderObj = window.InOptieKalenderData || {};
const InOptieKalenderData = InOptieKalenderObj.inoptieDates || [];
const InOptieKalenderMessage = document.getElementById('receive-message-inoptiekalender');
const InOptieSubmitKnop = document.getElementById('receive-message-inoptieSubmitKnop');
const generalMessage = document.getElementById('receive-message-general');
const flashMessageInOptieAfterNumberOfStudentsChangeGeneral = document.getElementById('receive-message-general-afterNumberOfStudentsChange') ?? null
const flashMessageInOptieAfterNumberOfStudentsChange = document.getElementById('change-number-message') ?? null;

const noAbsolutePositionIDSArray = ['receive-message-inoptiekalender', 'receive-message-general' ];
const allInputsAantallen = document.querySelectorAll('input[id^="aantallen-"]');



/** || ---------------functies------------------------||  */
//bericht voor een duur laten zien en naar toe scrollen
function showFlashMessage(flashMessage, duration = 5000, text = '') {
        if (!flashMessage) return;
        const elementId = flashMessage.id;
        if (!elementId) return;

        if (text !==''){
            flashMessage.textContent = flashMessage.textContent = String(text.trim()).replace(/[";]+/g, '');
        }
        if (flashMessage && flashMessage.textContent.trim() !== '') {
            if (text !== ''){
                flashMessage.style.display = 'flex';
            } else {
                flashMessage.style.display = 'flex';
                if (!noAbsolutePositionIDSArray.includes(elementId)) {
                    if (elementId !== 'change-number-message') {
                    flashMessage.classList.add('absolute-positioned-General');
                    } else {
                        console.log(flashMessage.classList);
                    }
                } else {
                    flashMessage.classList.add('flash-message-alternative');
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


function initFlatpickr(datesWithRequests = null) {
    const dateMap = new Map();
    if (datesWithRequests !== null) {
    datesWithRequests.forEach(item => {
        const dateKey = new Date(item.bezoekdatum);
        const dateKeySQL = getISODate(dateKey);
        dateMap.set(dateKeySQL, item.aantal_scholen);
    });}

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
                applyDayStyles(dayElem, aantalScholen,'bekijkaanvraag');

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

// Validatiefunctie voor het invoerveld
/** 
 * todo: in de php de inputvelden een duidelijke id meegeven
 * todo: in js via showmessage al laten zien dat het 0 of 200 moeten zijn binnen die range
 * 
 */
function valideerInput(amountInput, numberOfStudents = null) {
    const cijferRegex = /^[0-9]+$/;
    let waarde = amountInput.value.trim();

    if (waarde === '') {
        amountInput.value = numberOfStudents;
        return "";
    }

    if (waarde.startsWith('0') && waarde.length > 1) {
        waarde = waarde.replace(/^0+/, '');
        amountInput.value = numberOfStudents;
    }

    if (!cijferRegex.test(waarde)) {
        amountInput.value = numberOfStudents;
        return "Voer een geldig getal in tussen 0 en 200";
    }

    const aantal = parseInt(waarde, 10);

    if (aantal < 0 || aantal > 200) {
        amountInput.value = numberOfStudents;
        return "Voer een geldig getal in tussen 0 en 200";
    }

    return "";
}
    



/** || ----------------- script logica (functies en constanten in gebruik) ---------------||| */

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
            toggle.querySelector("span").textContent = "Verberg";
        } else {
            // Voeg hier de check toe voor bezoektijdenInfo
            if (targetId === "daginoptieaanvragenInfo") {
                toggle.querySelector("span").textContent = "Meer informatie over het bekijken van de aanvragen in optie";
            } 
        }
    })});

    const spinButtons = document.querySelectorAll('.spin-button');

    spinButtons.forEach(button => {
        button.addEventListener('click', function() {
          const input = this.parentElement.querySelector('input[type="number"]');
          const currentValue = parseInt(input.value);
          const min = parseInt(input.min);
          const max = parseInt(input.max);
          const step = parseInt(input.step) || 1;
    
          if (this.classList.contains('spin-up')) {
            if (currentValue < max) {
              input.value = currentValue + step;
            }
          } else if (this.classList.contains('spin-down')) {
            if (currentValue > min) {
              input.value = currentValue - step;
            }
          }
        });
      });


if (InOptieKalenderData.length > 0){
    initFlatpickr(InOptieKalenderData);
} else {
    initFlatpickr();
}
 
if (InOptieKalenderMessage) {
    showFlashMessage(InOptieKalenderMessage);
}
if (generalMessage){
    showFlashMessage(generalMessage);
}

if (InOptieSubmitKnop){
    showFlashMessage(InOptieSubmitKnop);
}

if (flashMessageInOptieAfterNumberOfStudentsChangeGeneral){
    showFlashMessage(flashMessageInOptieAfterNumberOfStudentsChangeGeneral);
}

if (flashMessageInOptieAfterNumberOfStudentsChange){
    showFlashMessage(flashMessageInOptieAfterNumberOfStudentsChange);
}


allInputsAantallen.forEach(input => {
    const numberOfStudents = input.value
    
    input.addEventListener('input', () => {
        const message = valideerInput(input, numberOfStudents);
        const hashed = input.id.split('-')[1];
        if (typeof(message) === 'string' && message !== ''){
            showFlashMessage(`meldingAantallenFromJS-${hashed}`, 5000, message);
        }
    })
});

