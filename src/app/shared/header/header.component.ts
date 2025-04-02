import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { SidebarService } from '../sidebar/services/sidebar.service';
import { Manufacturer } from '../models/manufacturer.model';

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.css']
})
export class HeaderComponent implements OnInit {
  isSidebarOpen = false;
  manufacturers: Manufacturer[] = [];
  selectedManufacturer: Manufacturer | null = null;

  constructor(
    private sidebarService: SidebarService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadManufacturers();
  }

  loadManufacturers(): void {
    this.sidebarService.getManufacturers().subscribe({
      next: (manufacturers) => {
        this.manufacturers = manufacturers;
      },
      error: (err) => {
        console.error('Error loading manufacturers:', err);
      }
    });
  }

  selectManufacturer(manufacturer: Manufacturer): void {
    // Navigate to catalog
    this.router.navigate(['/catalog']);

    // Select manufacturer in sidebar service
    this.sidebarService.selectManufacturer(manufacturer.id);

    // Update selected manufacturer
    this.selectedManufacturer = manufacturer;
  }


}
