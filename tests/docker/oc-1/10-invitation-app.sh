#!/bin/bash

# This script triggers installation of the Collaboration app right before server startup

echo 'Install Collaboration app'
occ app:enable invitation
