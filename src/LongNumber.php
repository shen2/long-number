<?php

class LongNumber {
    /**
     * @var int $digitWidth
     */
    protected $digitWidth;
    
	/**
     * @var array $digits
     */
    protected $digits;
    
    /**
     * @var bool $positive
     */
    protected $positive;
    
    /**
     * @param int $digitWidth
     * @param array $digits
     */
    public function __construct($digitWidth, array $digits = array(0), $positive = true){
        $this->digitWidth = $digitWidth;
        $this->digits = $digits;
        $this->positive = $positive;
    }
    
    /**
     * 
     * @param string $decimal
     * @return self
     */
    public static function createFromDecimal($decimal){
    	if ($decimal{0} === '-'){
    		$positive = false;
    		$decimal = substr($decimal, 1);
    	}
    	else{
    		$positive = true;
    	}
    	
    	$digits = array();
    	$digitWidth = 6;
    	$strlen = strlen($decimal);
    	for($pos = 0; ($pos + 1) * $digitWidth < $strlen; $pos ++){
    		$digits[$pos] = (int) substr($decimal, - ($pos + 1) * $digitWidth, $digitWidth);
    	}
    	$digits[$pos] = (int) substr($decimal, 0, ($strlen - 1) % $digitWidth + 1);
    	
    	return new self($digitWidth, $digits, $positive);
    }
    
    /**
     * @param string $binary
     * @return self;
     */
    public static function createFromBinary($binary, $signed = false){
        $step = 11;
        $digitWidth = 6;
        
        $bitComparer = (1 << $step) - 1;
        $base = pow(10, $digitWidth);
        
        if ($signed && (ord($binary{0}) & 0x80)){
        	$positive = false;
        	$binary ^= str_repeat("\xff", strlen($binary));
        }
        else{
        	$positive = true;
        }
        
        $binary = ltrim($binary, "\0");
        $strlen = strlen($binary);
        
        $digits = array(0);
        
        for($pos = (int) ceil($strlen * 8 / $step) - 1; $pos >= 0; $pos --){
            $strPos = $strlen - 1 - (int) ($pos * $step / 8);
            $moveRight = $pos * $step % 8;
            $currentPos = ord($binary{$strPos}) >> $moveRight;
            for($higher = 1;  ($higher * 8 - $moveRight < $step) && $strPos - $higher >= 0; $higher ++){
                $currentPos |= ord($binary{$strPos - $higher}) << ($higher * 8 - $moveRight);
            }
        	
            $carry = $currentPos & $bitComparer;
            
            if ($pos === 0 && !$positive)
            	$carry++;
        	
            $length = count($digits);
            for($i = 0; $i < $length; $i++){
                $carry += $digits[$i] << $step;
                $digits[$i] = $carry % $base;
                $carry = (int) ($carry / $base);
            }
        
            if ($carry > 0)
                $digits[$length] = $carry;
        }
        
        return new self($digitWidth, $digits, $positive);
    }
    
    /**
     * @param string $binary
     * @return self
     */
    public static function convertBinaryToString($binary, $signed = false){
    	if ($signed && (ord($binary{0}) & 0x80)){
    		$trimedBinary = ltrim($binary, "\xff");
    		if (strlen($trimedBinary) < 4){
    			$trimedBinary = str_pad($trimedBinary, 4, "\xff", STR_PAD_LEFT);
    			$unpacked = unpack('l', strrev($trimedBinary));
    			return sprintf("%d", $unpacked[1]);
    		}
    		elseif(strlen($trimedBinary) === 4 && (ord($trimedBinary{0}) & 0x80)){
    			$unpacked = unpack('l', strrev($trimedBinary));
    			return sprintf("%d", $unpacked[1]);
    		}
    	}
    	else{
	    	$trimedBinary = ltrim($binary, "\0");
	        if (strlen($trimedBinary) <= 4){
	        	$trimedBinary = str_pad($trimedBinary, 4, "\0", STR_PAD_LEFT);
	            $unpacked = unpack('N', $trimedBinary);
	            return sprintf("%u", $unpacked[1]);
	        }
    	}
        
        return self::createFromBinary($binary, $signed)->__toString();
    }

    /**
     * @return string
     */
    public function __toString(){
    	$str = $this->positive ? '' : '-';
        $str .= sprintf('%d', $this->digits[count($this->digits) - 1]);
        for($i = count($this->digits) - 2; $i >=0; $i--){
            $str .= sprintf("%0{$this->digitWidth}d", $this->digits[$i]);
        }
        return $str;
    }
}
