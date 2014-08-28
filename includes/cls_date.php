<?php

class   Lunar   {   
       var   $year;   
       var   $month;   
       var   $day;   
       var   $isLeap;   
       var   $yearCyl;   
       var   $dayCyl;   
       var   $monCyl;   
       var   $time;   
       var   $lunarInfo   =   array(   
           0x04bd8,0x04ae0,0x0a570,0x054d5,0x0d260,0x0d950,0x16554,0x056a0,0x09ad0,0x055d2,   
           0x04ae0,0x0a5b6,0x0a4d0,0x0d250,0x1d255,0x0b540,0x0d6a0,0x0ada2,0x095b0,0x14977,   
           0x04970,0x0a4b0,0x0b4b5,0x06a50,0x06d40,0x1ab54,0x02b60,0x09570,0x052f2,0x04970,   
           0x06566,0x0d4a0,0x0ea50,0x06e95,0x05ad0,0x02b60,0x186e3,0x092e0,0x1c8d7,0x0c950,   
           0x0d4a0,0x1d8a6,0x0b550,0x056a0,0x1a5b4,0x025d0,0x092d0,0x0d2b2,0x0a950,0x0b557,   
           0x06ca0,0x0b550,0x15355,0x04da0,0x0a5d0,0x14573,0x052d0,0x0a9a8,0x0e950,0x06aa0,   
           0x0aea6,0x0ab50,0x04b60,0x0aae4,0x0a570,0x05260,0x0f263,0x0d950,0x05b57,0x056a0,   
           0x096d0,0x04dd5,0x04ad0,0x0a4d0,0x0d4d4,0x0d250,0x0d558,0x0b540,0x0b5a0,0x195a6,   
           0x095b0,0x049b0,0x0a974,0x0a4b0,0x0b27a,0x06a50,0x06d40,0x0af46,0x0ab60,0x09570,   
           0x04af5,0x04970,0x064b0,0x074a3,0x0ea50,0x06b58,0x055c0,0x0ab60,0x096d5,0x092e0,   
           0x0c960,0x0d954,0x0d4a0,0x0da50,0x07552,0x056a0,0x0abb7,0x025d0,0x092d0,0x0cab5,   
           0x0a950,0x0b4a0,0x0baa4,0x0ad50,0x055d9,0x04ba0,0x0a5b0,0x15176,0x052b0,0x0a930,   
           0x07954,0x06aa0,0x0ad50,0x05b52,0x04b60,0x0a6e6,0x0a4e0,0x0d260,0x0ea65,0x0d530,   
           0x05aa0,0x076a3,0x096d0,0x04bd7,0x04ad0,0x0a4d0,0x1d0b6,0x0d250,0x0d520,0x0dd45,   
           0x0b5a0,0x056d0,0x055b2,0x049b0,0x0a577,0x0a4b0,0x0aa50,0x1b255,0x06d20,0x0ada0,   
           0x14b63);   
    
       /**   
         *   传回农历   y年的总天数   
         */   
       function   lYearDays($y)   {   
           $sum   =   348;   
           for($i=0x8000;   $i>0x8;   $i>>=1)   
               $sum   +=   ($this->lunarInfo[$y-1900]   &   $i)?   1:   0;   
           return   $sum+$this->leapDays($y);   
       }   
       /**   
         *   传回农历   y年闰月的天数   
         */   
       function   leapDays($y)   {   
           if($this->leapMonth($y))   
               return   ($this->lunarInfo[$y-1900]   &   0x10000)?   30:   29;   
           else   return   0;   
       }   
       /**   
         *   传回农历   y年闰哪个月   1-12   ,   没闰传回   0   
         */   
       function   leapMonth($y)   {   
           return   $this->lunarInfo[$y-1900]   &   0xf;   
       }   
       /**   
         *   传回农历   y年m月的总天数   
         */   
       function   monthDays($y,$m)   {   
           return   ($this->lunarInfo[$y-1900]   &   (0x10000>>$m))?   30:   29;   
       }   
       /**   
         *   创建农历日期对象   
         */   
       function   Lunar($objDate,$month=1,$day=1)   {   
           $leap=0;   
           $temp=0;   
           if(is_object($objDate))
   {
               $this->time   =   mktime(0,0,0,$objDate->getMonth(),$objDate->getDate(),$objDate->getYear()); 
     echo "ddd";
   }
           else   {   
               $year   =   $objDate;   
               $this->time   =   mktime(0,0,0,$month,$day,$year);      
           }   
           $offset   =   round($this->time/86400+25537);   
    
           $this->dayCyl   =   $offset   +   40;   
           $this->monCyl   =   14;   
    
           for($i=1900;   $i<$year   &&   $offset>0;   $i++)   {   
               $temp   =   $this->lYearDays($i);   
               $offset   -=   $temp;   
               $this->monCyl   +=   12;   
           }   
    
           if($offset<0)   {   
               $offset   +=   $temp;   
               $i--;   
               $this->monCyl   -=   12;   
           }   
    
           $this->year   =   $i;   
           $this->yearCyl   =   $i-1864;   
           $leap   =   $this->leapMonth($i);   //闰哪个月   
    
           $this->isLeap   =   false;   
           for($i=1;   $i<13   &&   $offset>0;   $i++)   {   
               //闰月   
               if($leap>0   &&   $i==($leap+1)   &&   $this->isLeap==false)   {   
                   $i--;   
                   $this->isLeap   =   true;   
                   $temp   =   $this->leapDays($this->year);   
               }else   {   
                   $temp   =   $this->monthDays($this->year,   $i);   
               }   
    
               //解除闰月   
               if($this->isLeap==true   &&   $i==($leap+1))   
                   $this->isLeap   =   false;   
    
               $offset   -=   $temp;   
               if($this->isLeap   ==   false)   
                   $this->monCyl   ++;   
           }   
    
           if($offset==0   &&   $leap>0   &&   $i==$leap+1)   
               if($this->isLeap)   
                   $this->isLeap   =   false;   
               else   {   
                   $this->isLeap   =   true;   
                   $i--;   
                   $this->monCyl--;   
               }   
    
           if($offset<0)   {   
               $offset   +=   $temp;   
               $i--;   
               $this->monCyl--;   
           }   
    
           $this->month   =   $i;   
           $this->day   =   $offset   +   1;   
       }     
       function   display()   {   
           $nStr   =   array('   ','01','02','03','04','05','06','07','08','09','10','11','12');   
           $nl   =   sprintf("%s%s-%s",($this->isLeap?"":""),$nStr[$this->month],$this->cDay($this->day));      
           RETURN   $nl;   
       }   
       /**   
         *   中文日期   
         */   
       function   cDay($d)   {   
           $nStr1   =   array('0','1','2','3','4','5','6','7','8','9','0');   
           $nStr2   =   array('0','1','2','3','　');   
     $s   =   $nStr2[floor($d/10)];   
           $s   .=   $nStr1[$d%10];
           return   $s;   
       }   
}
/*
$ld   =new Lunar(2012, 12, 11);
$nl   =$ld->display();
echo $nl;
 */

lange_account();
?>
