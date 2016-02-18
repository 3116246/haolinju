using System;
using System.Data;
using System.Diagnostics;


/// <summary>
/// CCLog 的摘要说明
/// LogLevel 默认为WARNING=3，可通过在配置文件CCLog.ini中LogLevel=3设置，可在运行中动态更改，每5秒检测一次CCLog.ini是否修改，有修改则载入最新的配置值
/// </summary>
public class CCLog
{
    public enum WriteLogMethods { WriteEvent, WriteFile };
    public static WriteLogMethods WriteLogMethod = WriteLogMethods.WriteFile;

    public enum LogLevels { NOLOG = 0, CRITICAL = 1, ERROR = 2, WARNING = 3, INFO = 4, DEBUG = 5, TRACE = 6 };
    public static LogLevels LogLevel = LogLevels.WARNING;

    public static void Critical(string s)
    {
        CheckLogConfig();
        if (LogLevel < LogLevels.CRITICAL) return;
        WriteLog("(C)" + s);
    }
    public static void Error(string s)
    {
        CheckLogConfig();
        if (LogLevel < LogLevels.ERROR) return;
        WriteLog("(E)" + s);
    }
    public static void Error(Exception e)
    {
        CheckLogConfig();
        if (LogLevel < LogLevels.ERROR) return;
        WriteLog("(E)" + e.Message + "\r\n" + e.StackTrace + "\r\n");
    }
    public static void Warning(string s)
    {
        CheckLogConfig();
        if (LogLevel < LogLevels.WARNING) return;
        WriteLog("(W)" + s);
    }
    public static void Info(string s)
    {
        CheckLogConfig();
        if (LogLevel < LogLevels.INFO) return;
        WriteLog("(I)" + s);
    }
    public static void Debug(string s)
    {
        CheckLogConfig();
        if (LogLevel < LogLevels.DEBUG) return;
        WriteLog("(D)" + s);
    }
    public static void Trace(string s)
    {
        CheckLogConfig();
        if (LogLevel < LogLevels.TRACE) return;
        WriteLog("(T)" + s);
    }

    public static void WriteLog(Exception e)
    {
        if (WriteLogMethod == WriteLogMethods.WriteEvent) WriteEventLog(e);
        else WriteFileLog(e);
    }

    public static void WriteLog(string s)
    {
        if (WriteLogMethod == WriteLogMethods.WriteEvent) WriteEventLog(s);
        else WriteFileLog(s);
    }

    private static EventLog Fel;
    private static EventLog el
    {
        get
        {
            if (Fel == null)
            {
                Fel = new EventLog();
                Fel.Source = "Application";
            }
            return Fel;
        }
    }

    private static void WriteEventLog(Exception e)
    {
        string s = DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss") + "：" + e.Message + "\r\n" + e.StackTrace;
        Console.WriteLine(s);
        el.WriteEntry(s);
    }

    private static void WriteEventLog(string s)
    {
        s = DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss") + "：" + s;
        Console.WriteLine(s);
        el.WriteEntry(s);
    }

    private static System.Threading.ManualResetEvent mreLog = new System.Threading.ManualResetEvent(true);
    private static System.Collections.Queue FqLog;
    private static System.Collections.Queue qLog
    {
        get 
        {
            if (FqLog == null)
            {
                System.Threading.Thread tLog = new System.Threading.Thread(new System.Threading.ThreadStart(FileLogWriteThread));
                tLog.Name = "CC日志记录线程";
                tLog.IsBackground = true;
                FqLog = new System.Collections.Queue();
                tLog.Start();
            }
            return FqLog;
        }
    }

    // 日志写入文件函数，异步接数据，并写入文件
    private static void FileLogWriteThread()
    {
        while (true)
        {
            try
            {
                mreLog.Reset();

                // 打开日志文件
                string dir = AppDomain.CurrentDomain.BaseDirectory + "\\Log";
                if (!System.IO.Directory.Exists(dir)) System.IO.Directory.CreateDirectory(dir);
                dir = dir + "\\" + DateTime.Today.ToString("yyyyMM");
                if (!System.IO.Directory.Exists(dir)) System.IO.Directory.CreateDirectory(dir);
                string filename = dir + "\\" + DateTime.Today.ToString("yyyyMMdd") + ".log";
                System.IO.FileStream fs = new System.IO.FileStream(filename, System.IO.FileMode.Append, System.IO.FileAccess.Write, System.IO.FileShare.ReadWrite);
                // 写入队列中的日志 
                while (qLog.Count > 0)
                {
                    byte[] info = System.Text.Encoding.Default.GetBytes(Convert.ToString(qLog.Dequeue()));
                    fs.Write(info, 0, info.Length);
                }
                fs.Close();

                mreLog.WaitOne();
            }
            catch (Exception)
            {
                //CCLog.WriteEventLog(ex);
            }
        }
    }


    private static void WriteFileLog(Exception e)
    {
        string s = DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss") + "：" + e.Message + "\r\n" + e.StackTrace + "\r\n";
        Console.Write(s);
        qLog.Enqueue(s);
        mreLog.Set();
    }

    private static void WriteFileLog(string s)
    {
        s = DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss") + "：" + s + "\r\n";
        Console.Write(s);
        qLog.Enqueue(s);
        mreLog.Set();
    }

    private static DateTime dtOldChecked = DateTime.MinValue;
    private static DateTime dtLastWriteLog = DateTime.MinValue;
    private static string logfilepath = AppDomain.CurrentDomain.BaseDirectory + "CCLog.ini";
    private static void CheckLogConfig()
    {
        if (dtOldChecked.AddSeconds(5) >= DateTime.Now) return;

        lock (logfilepath)
        {
            if (!System.IO.File.Exists(logfilepath))
            {
                if (LogLevel != LogLevels.WARNING) LogLevel = LogLevels.WARNING;
                return;
            }

            System.IO.FileInfo fiLogFile = new System.IO.FileInfo(logfilepath);
            if (fiLogFile.LastWriteTime < dtLastWriteLog) return;

            dtLastWriteLog = fiLogFile.LastWriteTime;
            System.IO.StreamReader srLogFile = fiLogFile.OpenText();
            string sLine = null;
            while ((sLine = srLogFile.ReadLine()) != null)
            {
                string[] ss = sLine.Split('=');
                if (ss.Length < 2) continue;

                int loglevel = (int)LogLevel;
                if (ss[0].Trim() == "LogLevel" && int.TryParse(ss[1].Trim(), out loglevel))
                {
                    LogLevel = (LogLevels)loglevel;
                    continue;
                }
            }
            srLogFile.Close();
        }
    }
}