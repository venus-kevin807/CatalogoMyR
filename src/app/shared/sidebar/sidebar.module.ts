import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms'; // Necesario para [(ngModel)]
import { SidebarComponent } from './components/sidebar.component';
import { ReactiveFormsModule } from '@angular/forms';
import { TermsModalComponent } from './components/terms-modal/terms-modal.component';
import { FooterComponent } from '../footer/footer.component';
import { AboutUsComponent } from './components/about-us/about-us.component';
import { MapModalComponent } from './components/map-modal/map-modal.component';

@NgModule({
  declarations: [SidebarComponent, TermsModalComponent, FooterComponent, AboutUsComponent, MapModalComponent],
  imports: [
    CommonModule,
    FormsModule,
    ReactiveFormsModule // Importamos ReactiveFormsModule para usar formularios reactivos
  ],
  exports: [SidebarComponent, FooterComponent] // Exportamos el componente para poder usarlo en otros módulos
})
export class SidebarModule {}
