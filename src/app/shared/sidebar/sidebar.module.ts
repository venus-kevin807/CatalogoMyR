import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms'; // Necesario para [(ngModel)]
import { SidebarComponent } from './components/sidebar.component';
import { ReactiveFormsModule } from '@angular/forms';
import { TermsModalComponent } from './components/terms-modal/terms-modal.component';

@NgModule({
  declarations: [SidebarComponent, TermsModalComponent],
  imports: [
    CommonModule,
    FormsModule,
    ReactiveFormsModule // Importamos ReactiveFormsModule para usar formularios reactivos
  ],
  exports: [SidebarComponent] // Exportamos el componente para poder usarlo en otros módulos
})
export class SidebarModule {}
