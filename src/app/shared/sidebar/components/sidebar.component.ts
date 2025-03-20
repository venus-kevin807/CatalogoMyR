// Add this to your sidebar.component.ts file to handle the toggle functionality
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-sidebar',
  templateUrl: './sidebar.component.html',
  styleUrls: ['./sidebar.component.css']
})
export class SidebarComponent implements OnInit {
  // Categories with their subcategories
  categories = [
    {
      name: 'Dirección',
      subcategories: ['Cremalleras', 'Bombas', 'Terminales', 'Barras'],
      showSubcategories: false
    },
    {
      name: 'Filtros',
      subcategories: ['Aceite', 'Aire', 'Combustible', 'Hidráulico'],
      showSubcategories: false
    },
    {
      name: 'Frenos',
      subcategories: ['Pastillas', 'Discos', 'Bombas', 'Zapatas'],
      showSubcategories: false
    },
    {
      name: 'Suspensión',
      subcategories: ['Amortiguadores', 'Espirales', 'Bujes', 'Bandejas'],
      showSubcategories: false
    },
    {
      name: 'Eléctricos',
      subcategories: ['Alternadores', 'Arranques', 'Fusibles', 'Baterías'],
      showSubcategories: false
    },
  ];

  // Manufacturers
  manufacturers = ['Toyota', 'Mitsubishi', 'Nissan', 'Heli', 'Hangcha', 'Tailift'];

  constructor() { }

  ngOnInit(): void {
  }

  // Toggle visibility of subcategories
  toggleSubcategories(category: any): void {
    category.showSubcategories = !category.showSubcategories;
  }
}
