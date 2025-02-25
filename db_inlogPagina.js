/** || --- consts ----- */
const inactivityMessage = document.getElementById('recieve-inactivity-message') || null;
const inlogSubmitMessage = document.getElementById('recieve-message-inlogSubmit') || null;


/** || ----- funcs ------- */

const AdjustPosition = (Element) => {
    Element.style.position = "absolute";
    const parent = Element.offsetParent; // De dichtstbijzijnde gepositioneerde ouder
    const rect = Element.getBoundingClientRect();
    const parentRect = parent.getBoundingClientRect();

    // Bereken de positie relatief aan de ouder
    if (Element.id)
    Element.style.left = `${rect.left - parentRect.left + 100}px`;
    Element.style.top = `${rect.top - parentRect.top - Element.offsetHeight - 35}px`;

}


function showFlashMessage(elementId, AdjustPosition = null, duration = 5000) {
    const flashMessage = document.getElementById(elementId);
    if (flashMessage && flashMessage.textContent.trim() !== '') {
        flashMessage.style.display = 'flex';
        
        if(AdjustPosition){
        AdjustPosition(flashMessage);}

        flashMessage.scrollIntoView({behavior:"smooth", block: "center"});
        setTimeout(() => {
            flashMessage.classList.add('fade-out'); // Start fade-out effect
            setTimeout(() => {
                flashMessage.style.display = 'none'; // Verberg na de fade
                flashMessage.classList.remove('fade-out'); // Reset voor hergebruik
            }, 1000); // Tijd overeenkomend met de CSS-transitie
        }, duration);
    }
}


/** || ------ display message ----|| */
if (inactivityMessage) {
    showFlashMessage('recieve-inactivity-message', AdjustPosition);
};

if (inlogSubmitMessage){
    showFlashMessage('recieve-message-inlogSubmit', AdjustPosition);
}



