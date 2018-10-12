#!/usr/bin/perl
use strict;
use warnings;
use Env;
use List::MoreUtils qw(uniq);
use Term::ANSIColor;
use Text::Table;




our $data =  `cat /etc/oratab`;
our @homes = ( $data =~ /:(.*)\:/g);
our @distinct_homes = uniq @homes;
our $ORACLE_HOME;
our $unix_path_variable;

our $hostname=`hostname`;
our $psu_patch_id;
our $psu_release_date;
our $one_off_patch_id;
our $ojvm_patch_id;
our $ojvm_release_date;
our $instance_name;


#------------------------------------------------------------------------------------------#
# Declare table
#------------------------------------------------------------------------------------------#
our $tb = Text::Table->new(
    "instance_name","hostname",  "ORACLE_HOME", "psu_patch_id", "psu_release_date", "ojvm_patch_id", "ojvm_release_date", "one_off_patch_id"
);


open (FILE, "/etc/oratab") || die "Cannot open your file";

while (my $line = <FILE> )
{
  chomp $line;

  our @sid = $line =~ /^(.*?):\//;
  foreach  (@sid)
  {
        $instance_name = "$_ \n";


        foreach (@distinct_homes)
        {

          #------------------------------------------------------------------------------------------#
          # Set up ORACLE_HOME
          #------------------------------------------------------------------------------------------#
           $ORACLE_HOME=$_;
           chomp $ORACLE_HOME;
           $ENV{ORACLE_HOME}=$ORACLE_HOME;


          #------------------------------------------------------------------------------------------#
          #Set up PATH
          #------------------------------------------------------------------------------------------#
           $unix_path_variable = $ENV{PATH} .= ":$ORACLE_HOME/bin";

          #------------------------------------------------------------------------------------------#
          # Get PSU Patch details
          #------------------------------------------------------------------------------------------#


           #-- Patch number
           my $psu_first_line_cmd  = "$ORACLE_HOME/OPatch/opatch lsinventory | grep -i -A 2 -B 2 -m 1 update | grep -m 1 Patch";
           my $get_first_line_string = `$psu_first_line_cmd`;

           $psu_patch_id = substr($get_first_line_string, 7,12);

           #-- PSU release date
            my $psu_third_line_cmd  = "$ORACLE_HOME/OPatch/opatch lsinventory | grep -i -A 2 -B 2 -m 1 update | grep -m 3 Update";
            my $get_third_line_string_psu = `$psu_third_line_cmd`;

            $psu_release_date = substr($get_third_line_string_psu, 58, 6);


          #-----------------------------------------------------------------------------------------#
          #Get OJVM patch details
          #-----------------------------------------------------------------------------------------#
          
        
          #-- OJVM patch number
          my $ojvm_first_line_cmd  = "$ORACLE_HOME/OPatch/opatch lsinventory | grep -i -A 2 -B 2 -m 1 javavm | grep -m 1 Patch";
          my $get_first_line_string_ojvm = `$ojvm_first_line_cmd`;

          $ojvm_patch_id = substr($get_first_line_string_ojvm, 7,12);


          #-- OJVM release date
          my $ojvm_third_line_cmd  = "$ORACLE_HOME/OPatch/opatch lsinventory | grep -i -A 2 -B 2 javavm | grep -m 3 Component";
          my $get_third_line_string_ojvm = `$ojvm_third_line_cmd`;

          $ojvm_release_date = substr($get_third_line_string_ojvm, 43, 6);


          #-----------------------------------------------------------------------------------------#
          #Get one off patch details
          #-----------------------------------------------------------------------------------------#

          #-- one off patch details
   my  $one_off_cmd = "$ORACLE_HOME/OPatch/opatch lsinventory | grep Patch  | grep -v OPatch | grep -v Unique | grep -v Database | grep -v $ojvm_patch_id | grep -v $psu_patch_id | grep -v version";
   my $one_off_output = `$one_off_cmd`;

   $one_off_patch_id = substr($one_off_output, 7,12);



        }


}

$tb->add($instance_name , $hostname, $ORACLE_HOME, $psu_patch_id, $psu_release_date,$ojvm_patch_id, $ojvm_release_date, $one_off_patch_id);

}
close (FILE);
print $tb;

