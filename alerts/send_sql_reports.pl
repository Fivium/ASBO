#!/usr/bin/perl
#
# $Id: //Infrastructure/GitHub/Database/asbo/alerts/send_sql_reports.pl#2 $
#
use strict;
use warnings;
use MIME::Lite;
use Time::Piece;
use Time::Seconds;

my $yesterday = localtime() - ONE_DAY;
my $yesterday_date_str = $yesterday->strftime('%Y_%m_%d');
my $asbo_server='x.x.x.x';
my $initial_db='any_db_from_db_lookup_conf_file';
my $get_all_reports_url='http://'.$asbo_server.'/show_page.php?db='.$initial_db.'&page=top_sql_report_all_dbs.php%3Fstart%3D'.$yesterday_date_str.'__09%26end%3D'.$yesterday_date_str.'__17%26order_by_id%3D1';
my $report_dir = '/home/oracle/email_top_sql_reports/reports';
my $all_reports_filename = 'sql_report_all.txt';
my $all_reports_filename_with_path="$report_dir/$all_reports_filename";
my $report_start_ind = '__START__';
my $report_filename_with_path;
my $writing_report = 0;
my $report_fh;
my @report_filenames;
my $msg;
my $mail_host='mail_host';
#
# Get the reports in one file
#
system('wget -O '.$all_reports_filename_with_path.' "'.$get_all_reports_url.'"');

open my $fh, '<', $all_reports_filename_with_path or die "Could not open $all_reports_filename_with_path : $!";

$msg = MIME::Lite->new(
    From    => 'from_email_address',
    To      => 'to_email_address',
    Subject => 'Top SQL reports all Databases',
    Type    => 'multipart/mixed'
);

$msg->attach (
  Type => 'TEXT',
  Data => 'See attachments'
);
#
# Break up the file as multiple attachments
#
while( my $line = <$fh>)  {
    if ($line =~ /$report_start_ind/) {
        $line =~ s/$report_start_ind//g;
        chomp($line);
        my $report_file_name = $line."_top_sql_report.html";
        print "\n$report_file_name\n";
        $report_filename_with_path = "$report_dir/$report_file_name";
        open $report_fh, '>', $report_filename_with_path or die "Could not open file '$report_filename_with_path' $!";
        $writing_report = 1;
        push @report_filenames, $report_filename_with_path;
        $msg->attach (
            Type =>'text/html; charset="iso-8859-1"',
            Path => $report_filename_with_path,
            Filename => $report_file_name,
            Disposition => 'attachment'
        );
    }
    if($writing_report){
        print $report_fh "$line";
    }
}
close $fh;
close $report_fh;

$msg->send('smtp', $mail_host);
