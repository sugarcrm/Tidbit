<?php

/*********************************************************************************
 * Tidbit is a data generation tool for the SugarCRM application developed by
 * SugarCRM, Inc. Copyright (C) 2004-2010 SugarCRM Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by SugarCRM".
 ********************************************************************************/

class Tidbit_Gibberish
{
    const BASE_TEXT = 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nunc pulvinar tellus et arcu. Integer venenatis nonummy risus. Sed turpis lorem, cursus sit amet, eleifend at, dapibus vitae, lacus. Nunc in leo ac justo volutpat accumsan. In venenatis consectetuer ante. Proin tempus sapien et sapien. Nunc ante turpis, bibendum sed, pharetra a, eleifend porta, augue. Curabitur et nulla. Proin tristique erat. In non turpis. In lorem mauris, iaculis ac, feugiat sed, bibendum eu, enim. Donec pede. Phasellus sem risus, fermentum in, imperdiet vel, mattis nec, justo. Nullam vitae risus. Fusce neque. Mauris malesuada euismod magna. Sed nibh pede, consectetuer quis, condimentum sit amet, pretium ut, eros. Quisque nec arcu. Sed ac neque. Maecenas volutpat erat ac est. Nam mauris. Sed condimentum cursus purus. Integer facilisis. Duis libero ante, cursus nec, congue nec, imperdiet et, ligula. Pellentesque porttitor suscipit nulla. Integer diam magna, luctus rutrum, luctus sit amet, euismod a, diam. Nunc vel eros faucibus velit lobortis faucibus. Phasellus ultrices, nisl id pulvinar fringilla, justo augue elementum enim, eget tincidunt dolor pede et tortor. Vestibulum at justo vitae sem auctor tincidunt. Maecenas facilisis volutpat dui. Pellentesque non justo. Quisque eleifend, tellus quis venenatis volutpat, ipsum purus cursus dolor, in aliquam magna sem.';
    static private $words = array();
    
    private $_number = null;
    
    public function __construct($number)
    {
        self::$words = explode(' ', self::BASE_TEXT);
        while (count(self::$words) < $number) {
            self::$words = array_merge(self::$words, self::$words);
        }
        $this->_number = $number;
    }
    
    public function __toString()
    {
        shuffle(self::$words);
        return implode(' ', array_slice(self::$words, 0, $this->_number));
    }
}
