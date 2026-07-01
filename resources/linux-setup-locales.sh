#!/usr/bin/env bash
#
# This file is part of the Phalcon Talon.
#
# (c) Phalcon Team <team@phalcon.io>
#
# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.

# Setup locales for tests - Runner::initEnvironment() sets LC_ALL to en_US.utf-8.
sudo apt-get install locales -y
sudo sed -i -e 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen
sudo dpkg-reconfigure --frontend=noninteractive locales
sudo update-locale LANG=en_US.UTF-8