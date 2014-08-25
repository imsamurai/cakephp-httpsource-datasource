CWD='..';
CAKE=$CWD/cakephp/lib/Cake;
APP=$CWD/cakephp/app;

#cakephp
mkdir -p $APP/TestSuite
mkdir -p $APP/TestSuite/Reporter

cat $CAKE/TestSuite/CakeTestSuiteCommand.php | sed "s/require_once 'PHPUnit\/TextUI\/Command.php';//" > $APP/TestSuite/CakeTestSuiteCommand.php;
cat $CAKE/TestSuite/CakeTestRunner.php | sed "s/require_once 'PHPUnit\/TextUI\/TestRunner.php';//" > $APP/TestSuite/CakeTestRunner.php;
cat $CAKE/TestSuite/Reporter/CakeBaseReporter.php | sed "s/require_once 'PHPUnit\/TextUI\/ResultPrinter.php';//" > $APP/TestSuite/Reporter/CakeBaseReporter.php;
cat $CAKE/TestSuite/Reporter/CakeHtmlReporter.php | sed 's/echo "<br \/>" . PHPUnit_Util_Diff::diff(\$expectedMsg, \$actualMsg);/\$differ = new SebastianBergmann\\Diff\\Differ;echo "<br \/>" . \$differ->diff(\$expectedMsg, \$actualMsg);/' > $APP/TestSuite/Reporter/CakeHtmlReporter.php;