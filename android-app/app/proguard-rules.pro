# Add project specific ProGuard rules here.
# By default, the flags in this file are appended to flags specified
# in proguard-android-optimize.txt
-keepattributes SourceFile,LineNumberTable
-renamesourcefileattribute SourceFile

# Keep Bluetooth-related classes
-keep class android.bluetooth.** { *; }

# Keep WebView JavaScript interface methods
-keepclassmembers class * {
    @android.webkit.JavascriptInterface <methods>;
}
