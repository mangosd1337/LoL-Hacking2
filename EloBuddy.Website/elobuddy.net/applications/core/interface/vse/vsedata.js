var ipsVSEData = {
	"sections": {
		"body": {
			"body": {
				"title": "Body",
				"selector": "body",
				"background": {
					"color": "ebeef2"
				},
				"font": {
					"color": "363636"
				},
				"settings": {
					"background": "page_background",
					"color": "text_color"
				}
			},
			"lightText": {
				"title": "Light text",
				"selector": ".ipsType_light",
				"font": {
					"color": "adadad"
				},
				"settings": {
					"font": "text_light"
				}
			},
			"link": {
				"title": "Link color",
				"selector": "a",
				"font": {
					"color": "3c6994"
				},
				"settings": {
					"font": "link_color"
				}
			},
			"linkHover": {
				"title": "Link hover color",
				"selector": "a:hover",
				"font": {
					"color": "ad1457"
				},
				"settings": {
					"font": "link_hover_color"
				}
			},
			"footer": {
				"title": "Footer links color",
				"selector": "#ipsLayout_footer a, #ipsLayout_footer p",
				"font": {
					"color": "333333"
				},
				"settings": {
					"font": "footer_links"
				}
			}
		},
		"header": {
			"appBar": {
				"title": "Main navigation",
				"selector": ".ipsNavBar_primary, #elMobileNav",
				"background": {
					"color": "304d66"
				},
				"settings": {
					"background": "main_nav"
				}
			},
			"appBarFont": {
				"title": "Main navigation font",
				"selector": ".ipsNavBar_primary > ul > li > a",
				"font": {
					"color": "ffffff"
				},
				"settings": {
					"font": "main_nav_font"
				}
			},
			"mainNavTab": {
				"title": "Active navigation tab",
				"selector": ".ipsNavBar_primary > ul > li.ipsNavBar_active > a, .ipsNavBar_primary:not( .ipsNavBar_noSubBars ) > ul:before, .ipsNavBar_secondary",
				"background": {
					"color": "f5f5f5"
				},
				"settings": {
					"background": "main_nav_tab"
				}
			},
			"mainNavTabFont": {
				"title": "Active navigation font",
				"selector": ".ipsNavBar_primary > ul > li.ipsNavBar_active > a, .ipsNavBar_secondary > li > a, .ipsNavBar_secondary > li > a:hover, .ipsNavBar_secondary > li.ipsNavBar_active a",
				"font": {
					"color": "333333"
				},
				"settings": {
					"font": "main_nav_tab_font"
				}
			},
			"headerBar": {
				"title": "Header",
				"selector": "#ipsLayout_header > header",
				"background": {
					"color": "3c6994"
				},
				"settings": {
					"background": "header"
				}
			},
			"siteName": {
				"title": "Site name text",
				"selector": "#elSiteTitle",
				"font": {
					"color": "ffffff"
				}
			}
		},
		"buttons": {
			"normalButton": {
				"title": "Normal button",
				"selector": ".ipsApp .ipsButton_normal",
				"background": {
					"color": "417ba3"
				},
				"font": {
					"color": "ffffff"
				},
				"settings": {
					"background": "normal_button",
					"font": "normal_button_font"
				}
			},
			"primaryButton": {
				"title": "Primary button",
				"selector": ".ipsApp .ipsButton_primary",
				"background": {
					"color": "262e33"
				},
				"font": {
					"color": "ffffff"
				},
				"settings": {
					"background": "primary_button",
					"font": "primary_button_font"
				}
			},
			"importantButton": {
				"title": "Important button",
				"selector": ".ipsApp .ipsButton_important",
				"background": {
					"color": "37848b"
				},
				"font": {
					"color": "ffffff"
				},
				"settings": {
					"background": "important_button",
					"font": "important_button_font"
				}
			},
			"alternateButton": {
				"title": "Alternate button",
				"selector": ".ipsApp .ipsButton_alternate",
				"background": {
					"color": "2d4760"
				},
				"font": {
					"color": "ffffff"
				},
				"settings": {
					"background": "alternate_button",
					"font": "alternate_button_font"
				}
			},
			"lightButton": {
				"title": "Light button",
				"selector": ".ipsApp .ipsButton_light",
				"background": {
					"color": "f0f0f0"
				},
				"font": {
					"color": "333333"
				},
				"settings": {
					"background": "light_button",
					"font": "light_button_font"
				}
			},
			"veryLightButton": {
				"title": "Very light button",
				"selector": ".ipsApp .ipsButton_veryLight",
				"background": {
					"color": "ffffff"
				},
				"font": {
					"color": "333333"
				},
				"settings": {
					"background": "very_light_button",
					"font": "very_light_button_font"
				}
			},
			"buttonBar": {
				"title": "Button Bar",
				"selector": ".ipsButtonBar",
				"background": {
					"color": "3c6994"
				},
				"settings": {
					"background": "button_bar"
				}
			}
		},
		"backgrounds": {
			"areaBackground": {
				"title": "Area background",
				"selector": ".ipsAreaBackground",
				"background": {
					"color": "ebebeb"
				},
				"settings": {
					"background": "area_background"
				}
			},
			"areaBackgroundLight": {
				"title": "Light area background",
				"selector": ".ipsAreaBackground_light",
				"background": {
					"color": "fafafa"
				},
				"settings": {
					"background": "area_background_light"
				}
			},
			"areaBackgroundReset": {
				"title": "Reset area background",
				"selector": ".ipsAreaBackground_reset",
				"background": {
					"color": "ffffff"
				},
				"settings": {
					"background": "area_background_reset"
				}
			},
			"areaBackgroundDark": {
				"title": "Dark area background",
				"selector": ".ipsAreaBackground_dark",
				"background": {
					"color": "262e33"
				},
				"settings": {
					"background": "area_background_dark"
				}
			}
		},
		"other": {
			"itemStatus": {
				"title": "Item status badge",
				"selector": ".ipsItemStatus.ipsItemStatus_large",
				"background": {
					"color": "3c6994"
				},
				"settings": {
					"background": "item_status"
				}
			},
			"tabBar": {
				"title": "Tab bar background",
				"selector": ".ipsTabs",
				"background": {
					"color": "3d648a"
				},
				"settings": {
					"background": "tab_background"
				}
			},
			"sectionTitle": {
				"title": "Section title bar",
				"selector": ".ipsType_sectionTitle",
				"background": {
					"color": "304d66"
				},
				"font": {
					"color": "ffffff"
				},
				"settings": {
					"background": "section_title",
					"font": "section_title_font"
				}
			},
			"profileHeader": {
				"title": "Default profile header",
				"selector": "#elProfileHeader",
				"background": {
					"color": "262e33"
				},
				"settings": {
					"background": "profile_header"
				}
			},
			"widgetTitleBar": {
				"title": "Widget title bar",
				"selector": ".ipsWidget.ipsWidget_vertical .ipsWidget_title",
				"background": {
					"color": "3c6994"
				},
				"settings": {
					"background": "widget_title_bar"
				}
			}
		}
	}
};

var colorizer = {
	primaryColor: {
		"body": [ 'background' ],
		"link": [ 'font' ],
		"appBar": [ 'background' ],
		"mainNavTab": [ 'background' ],
		"headerBar": [ 'background' ],
		"normalButton": [ 'background' ],
		"primaryButton": [ 'background' ],
		"alternateButton": [ 'background' ],
		"sectionTitle": [ 'background' ],
		"areaBackgroundDark": [ 'background' ],
		"profileHeader": [ 'background' ],
		"link": [ 'font' ],
		"widgetTitleBar": [ 'background' ],
		"buttonBar": [ 'background' ],
		"tabBar": [ 'background' ],
		"itemStatus": [ 'background' ]
	},
	secondaryColor: {
		"linkHover": [ 'font' ],
		"importantButton": [ 'background' ]
	},
	tertiaryColor: {
		"areaBackground": [ 'background' ],
		"areaBackgroundLight": [ 'background' ],
		"areaBackgroundReset": [ 'background' ]
	},
	textColor: {
		"body": [ 'font' ],
		"lightText": [ 'font' ],
		"mainNavTabFont": [ 'font' ],
		"footer": [ 'font' ]
	},
	startColors: {
		"primaryColor": "3c6994",
		"secondaryColor": "37848b",
		"tertiaryColor": "f3f3f3",
		"textColor": "272a34"
	}
};