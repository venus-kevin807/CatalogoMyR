import { Component } from '@angular/core';

@Component({
  selector: 'app-footer',
  templateUrl: './footer.component.html',
  styleUrl: './footer.component.css'
})
export class FooterComponent {
  isTermsModalOpen = false;
  isAboutUsOpen = false;
  isMapModalOpen = false;

  openTermsModal(event: Event): void {
    event.preventDefault();
    this.isTermsModalOpen = true;
  }

  closeTermsModal(): void {
    this.isTermsModalOpen = false;
  }

  openAboutUs(event: Event): void {
    event.preventDefault();
    this.isAboutUsOpen = true;
  }

  closeAboutUs(): void {
    this.isAboutUsOpen = false;
  }

  openMapModal(event: Event): void {
    event.preventDefault();
    this.isMapModalOpen = true;
  }

  closeMapModal(): void {
    this.isMapModalOpen = false;
  }
}
