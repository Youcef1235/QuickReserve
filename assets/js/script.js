/**
 * QuickReserve - Script principal
 */

document.addEventListener("DOMContentLoaded", () => {
  // Fermer automatiquement les alertes après 5 secondes
  const alerts = document.querySelectorAll(".alert")
  alerts.forEach((alert) => {
    if (!alert.classList.contains("alert-permanent")) {
      setTimeout(() => {
        const bsAlert = bootstrap.Alert(alert)
        bsAlert.close()
      }, 5000)
    }
  })

  // Activer les tooltips Bootstrap
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipTriggerList.map((tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl))

  // Activer les popovers Bootstrap
  const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
  popoverTriggerList.map((popoverTriggerEl) => new bootstrap.Popover(popoverTriggerEl))

  // Validation des formulaires
  const forms = document.querySelectorAll(".needs-validation")
  Array.from(forms).forEach((form) => {
    form.addEventListener(
      "submit",
      (event) => {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }
        form.classList.add("was-validated")
      },
      false,
    )
  })

  // Gestion des onglets avec stockage dans l'URL
  const triggerTabList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tab"]'))
  triggerTabList.forEach((triggerEl) => {
    const tabTrigger = new bootstrap.Tab(triggerEl)

    triggerEl.addEventListener("click", function (event) {
      event.preventDefault()
      tabTrigger.show()

      // Mettre à jour l'URL avec l'ID de l'onglet
      const tabId = this.getAttribute("href").substring(1)
      history.replaceState(null, null, `?tab=${tabId}`)
    })
  })

  // Activer l'onglet depuis l'URL
  const urlParams = new URLSearchParams(window.location.search)
  const tabId = urlParams.get("tab")
  if (tabId) {
    const tab = document.querySelector(`[data-bs-toggle="tab"][href="#${tabId}"]`)
    if (tab) {
      const tabInstance = new bootstrap.Tab(tab)
      tabInstance.show()
    }
  }

  // Animation au défilement
  const animatedElements = document.querySelectorAll(".animate-on-scroll")

  function checkIfInView() {
    const windowHeight = window.innerHeight
    const windowTopPosition = window.scrollY
    const windowBottomPosition = windowTopPosition + windowHeight

    animatedElements.forEach((element) => {
      const elementHeight = element.offsetHeight
      const elementTopPosition = element.offsetTop
      const elementBottomPosition = elementTopPosition + elementHeight

      if (elementBottomPosition >= windowTopPosition && elementTopPosition <= windowBottomPosition) {
        element.classList.add("animated")
      }
    })
  }

  window.addEventListener("scroll", checkIfInView)
  window.addEventListener("load", checkIfInView)
})

/**
 * Fonction pour confirmer une action
 * @param {string} message - Message de confirmation
 * @returns {boolean} - True si confirmé, false sinon
 */
function confirmAction(message) {
  return confirm(message)
}

/**
 * Fonction pour formater une date
 * @param {string} dateStr - Date au format YYYY-MM-DD
 * @returns {string} - Date formatée
 */
function formatDate(dateStr) {
  const options = { year: "numeric", month: "long", day: "numeric" }
  const date = new Date(dateStr)
  return date.toLocaleDateString("fr-FR", options)
}

/**
 * Fonction pour charger les créneaux horaires disponibles
 * @param {number} serviceId - ID du service
 * @param {string} date - Date au format YYYY-MM-DD
 */
function loadTimeSlots(serviceId, date) {
  const container = document.getElementById("time-slots")
  if (!container) return

  container.innerHTML = '<p class="text-center"><i class="bi bi-hourglass-split"></i> Chargement des créneaux...</p>'

  fetch(`api/time-slots.php?service_id=${serviceId}&date=${date}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.length === 0) {
        container.innerHTML = '<div class="alert alert-info">Aucun créneau disponible pour cette date.</div>'
        return
      }

      let html = '<div class="row">'
      data.forEach((slot) => {
        html += `
                <div class="col-md-3 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="time_slot" id="slot_${slot.start}" value="${slot.start}-${slot.end}">
                        <label class="form-check-label" for="slot_${slot.start}">
                            ${slot.start} - ${slot.end}
                        </label>
                    </div>
                </div>`
      })
      html += "</div>"

      container.innerHTML = html

      // Ajouter les écouteurs d'événements
      document.querySelectorAll('input[name="time_slot"]').forEach((input) => {
        input.addEventListener("change", updateRecap)
      })
    })
    .catch((error) => {
      container.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des créneaux.</div>'
      console.error("Erreur:", error)
    })
}

/**
 * Fonction pour mettre à jour le récapitulatif de réservation
 */
function updateRecap() {
  const selectedSlot = document.querySelector('input[name="time_slot"]:checked')
  const recapTime = document.getElementById("recap-time")
  const startTimeInput = document.getElementById("start_time")
  const endTimeInput = document.getElementById("end_time")

  if (selectedSlot && recapTime && startTimeInput && endTimeInput) {
    const timeSlot = selectedSlot.value.split("-")
    startTimeInput.value = timeSlot[0]
    endTimeInput.value = timeSlot[1]
    recapTime.textContent = timeSlot[0] + " - " + timeSlot[1]
  }
}

