import React from 'react';
import BaseComponent from './BaseComponent';
import { MenuItem, Drawer } from 'material-ui';

export default class NavigationDrawer extends BaseComponent {
  constructor(props) {
    super(props);
    this.state = { open: false };
  }
  onTouchTap() {
    
  }
  render() {
    return (
      <div className="NavigationDrawer">
          <Drawer
            docked={false}
            open={this.state.open}
            onRequestChange={(open) => this.setState({open})}
          >
            <MenuItem onTouchTap={this.onTouchTap.bind(this)}>Menu Item</MenuItem>
            <MenuItem onTouchTap={this.onTouchTap.bind(this)}>Menu Item 2</MenuItem>
          </Drawer>
      </div>
    );
  }
}
