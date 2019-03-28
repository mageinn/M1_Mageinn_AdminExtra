# AdminExtra for Magento® 1

## Description

It's very common that after a product is imported for a specific store, Magento unchecks "Use Default Config" for every attribute on that particular store level. Unfortunately there doesn't appear to be an option in Magento to reset the attribute's value back to "Use Default Config" for the mass action, "Update attributes".

This extension allows you to reset/set the“Use Default Value” option for any attribute and image on a store level using admin mass action. There is also an added new feature that allows you to copy the default value of any attribute into a particular store view!

### Features

* Reset/Set "Use Default Value" for any attribute and images
* Copy Default Value of any attribute into a speacific store view

## User Guide

### 1. Configuration

To enable module go to “System->Configuration->Mageinn->AdminExtra” section.

There are 2 settings:
1. Enabled – switches module on/off

2. Don’t copy images when duplicating product – allows to clear images gallery on product duplicate.

3. Reset URL key when duplicating – allows to unset “Create Permanent Redirect for old URL” checkbox when duplicating product

Set “Enabled” parameter to “Yes” and click “Save Config” button to save configuration.

### 2. Product Duplication
If you don’t want to copy images when duplicating product, module allows you to do that. Set module’s configuration parameter “Don’t copy images when duplicating product” to “Yes”. Then when you click “Save & Duplicate” button on product edit page media gallery will be cleared for the new product.

### 3. Attributes Mass Action
Module allows you to set/reset “Use Default Value” option for any attributes and images on a store level using admin mass action.

Select several products and choose “Update attributes” action from dropdown.

Select Store View first and then set required “Use Default” checkboxes or “Copy from Default”.

### 4. Enjoy it :) 