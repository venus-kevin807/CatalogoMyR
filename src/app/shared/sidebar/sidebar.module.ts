import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms'; // Necesario para [(ngModel)]
import { SidebarComponent } from './components/sidebar.component';

@NgModule({
  declarations: [SidebarComponent],
  imports: [
    CommonModule,
    FormsModule // Importamos FormsModule para utilizar [(ngModel)]
  ],
  exports: [SidebarComponent] // Exportamos el componente para poder usarlo en otros módulos
})
export class SidebarModule {}
