// progress-indicator.js

class ProgressIndicator extends HTMLElement {
    constructor() {
      super();
  
      // Calculate the circle radius and the normalised version which is radius minus the stroke width
      const radius = this.viewBox / 2;
      const normalisedRadius = radius - this.stroke;
      this.calculatedCircumference = normalisedRadius * 2 * Math.PI;
  
      // Set the custom property viewbox value for our CSS to latch on to
      this.style.setProperty("--progress-indicator-viewbox", `${this.viewBox}px`);
  
      // Set the default aria role states
      this.setAttribute("aria-label", this.label);
      this.setAttribute("role", "progressbar");
      this.setAttribute("aria-valuemax", "100");
  
      // Render the component with all the data ready
      this.innerHTML = `
        <div class="progress-indicator">
          <div class="progress-indicator__visual">
            <div data-progress-count class="progress-indicator__count"></div>
            <svg 
              fill="none" 
              viewBox="0 0 ${this.viewBox} ${this.viewBox}"
              width="${this.viewBox}"
              height="${this.viewBox}"
              focusable="false"
              class="progress-indicator__circle"
            >
              <circle 
                r="${normalisedRadius}"
                cx="${radius}"
                cy="${radius}"
                stroke-width="${this.stroke}"
                class="progress-indicator__background-circle"
              />
              <circle 
                r="${normalisedRadius}"
                cx="${radius}"
                cy="${radius}"
                stroke-dasharray="${this.calculatedCircumference} ${this.calculatedCircumference}"
                stroke-width="${this.stroke}"
                class="progress-indicator__progress-circle"
                data-progress-circle
              />
            </svg>
            <svg 
              class="progress-indicator__check"
              focusable="false" 
              viewBox="0 0 20 20" 
              fill="none"
            >
              <path d="m8.335 12.643 7.66-7.66 1.179 1.178L8.334 15 3.032 9.697 4.21 8.518l4.125 4.125Z" fill="currentColor"/>
            </svg>
          </div>
        </div>
      `;
    }
  
    setProgress(percent) {
      // Always make sure the percentage passed never exceeds the max
      if (percent > 100) {
        percent = 100;
      }
  
      // Set the aria role value for screen readers
      this.setAttribute("aria-valuenow", percent);
  
      const circle = this.querySelector("[data-progress-circle]");
      const progressCount = this.querySelector("[data-progress-count]");
  
      // Calculate a dash offset value based on the calculated circumference and the current percentage
      circle.style.strokeDashoffset =
        this.calculatedCircumference -
        (percent / 100) * this.calculatedCircumference;
  
      // A human readable version for the text label
      progressCount.innerText = `${percent}%`;
  
      // Set a complete or pending state based on progress
      if (percent == 100) {
        this.setAttribute("data-progress-state", "complete");
      } else {
        this.setAttribute("data-progress-state", "pending");
      }
    }
  
    // Observe the progress attribute for changes
    static get observedAttributes() {
      return ["progress"];
    }
  
    get viewBox() {
      return this.getAttribute("viewbox") || 100;
    }
  
    get stroke() {
      return this.getAttribute("stroke") || 5;
    }
  
    get label() {
      return this.getAttribute("label") || "Current progress";
    }
  
    // Listen to the progress attribute and if it's changed, trigger the set progress method
    attributeChangedCallback(name, oldValue, newValue) {
      if (name === "progress") {
        this.setProgress(newValue);
      }
    }
  }
  
  customElements.define("progress-indicator", ProgressIndicator);
  
  // Example usage with simulated progress
  let progress = 0;
  const indicator = document.querySelector("progress-indicator");
  
  const timer = setInterval(() => {
    progress += 10;
    indicator.setAttribute("progress", progress);
  
    if (progress === 100) {
      setTimeout(() => (progress = 0), 3000); // Reset progress after 3 seconds
    }
  }, 1000);
  