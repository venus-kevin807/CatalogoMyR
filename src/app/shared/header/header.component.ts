import { Component, OnInit } from '@angular/core';
import { SidebarService } from '../sidebar/services/sidebar.service';
import { Manufacturer } from '../models/manufacturer.model';

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.css']
})
export class HeaderComponent implements OnInit {
  navItems: { name: string; link: string }[] = [];

  constructor(private sidebarService: SidebarService) {}

  ngOnInit(): void {
    this.sidebarService.getManufacturers().subscribe(manufacturers => {
      this.navItems = manufacturers.map(manufacturer => ({
        name: manufacturer.name.toUpperCase(),
        link: `/${manufacturer.name.toLowerCase().replace(/\s+/g, '-')}`
      }));
    });
  }
}
