package unicredit.voicerecognition;

import android.app.Activity;
import android.app.SearchManager;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.content.pm.ResolveInfo;
import android.os.Bundle;
import android.speech.RecognizerIntent;
import android.view.View;
import android.widget.AdapterView;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;

import java.lang.reflect.Method;
import java.util.ArrayList;
import java.util.List;

/**
 * Created by John on 8/1/2016.
 */
public class VoiceTextActivity extends Activity{
    private static final int VOICE_RECOGNITION_REQUEST_CODE = 1001;

    private Button recBut;
    private EditText edText;

    float[] pattern_data;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_voice_text);
        recBut = (Button) findViewById(R.id.recButton);
        edText = (EditText) findViewById(R.id.recTextBox);
        edText.setKeyListener(null);
        checkVoiceRecognition();
    }

    public void goBack(View view) {
        super.onBackPressed();
    }

    public void checkVoiceRecognition() {
        // Check if voice recognition is present
        PackageManager pm = getPackageManager();
        List<ResolveInfo> activities = pm.queryIntentActivities(new Intent(
                RecognizerIntent.ACTION_RECOGNIZE_SPEECH), 0);
        if (activities.size() == 0) {
            onDataAvailable(null);
            recBut.setEnabled(false);
            edText.setText("Voice recognizer not present");
            Toast.makeText(this, "Voice recognizer not present",
                    Toast.LENGTH_SHORT).show();
        }
    }

    /**
     * Bit reversal, used for FFT.
     *
     * @param data - in/out data
     */
    static void bit_reversal(float[] data)
    {
        int i, j;
        int n, m, nn;
        float cop;

        nn = data.length;
        n = nn * 2;
        j = 0;

        for (i = 0; i < nn; i += 2) {
            if (j > i) {
			/* swap the real part */
                cop = data[j];
                data[j] = data[i];
                data[i] = cop;

			/* swap the complex part */
                cop = data[j+1];
                data[j+1] = data[i+1];
                data[i+1] = cop;

			/*
			 * checks if the changes occurs in the first half
			 * and use the mirrored effect on the second half
			 */
                if (j / 2 < n / 4) {
				/* swap the real part */
                    cop = data[n - (j + 2)];
                    data[n - (j + 2)] = data[n - (i + 2)];
                    data[n - (i + 2)] = cop;

				/* swap the complex part */
                    cop = data[n - (j + 2) + 1];
                    data[n - (j + 2) + 1] = data[n - (i + 2) + 1];
                    data[n - (i + 2) + 1] = cop;
                }
            }

            m = n / 2;

            while (m >= 2 && j >= m) {
                j -= m;
                m /= 2;
            }

            j += m;
        }
    }

    /**
     * Fast Fourier transform.
     *
     * @param data - in/out data (in - sample, out - frequency)
     */
    void fft(float[] data)
    {
        int n, m, mmax, istep, i, j, nn;
        double wtemp, wr, wpr, wpi, wi, theta;
        float tempr, tempi;

        nn = data.length;
        n = nn * 2;
        bit_reversal(data);

        mmax = 2;
        while (n > mmax) {
            istep = mmax * 2;
            theta = 2 * Math.PI / mmax;
            wtemp = Math.sin(0.5 * theta);

            wpr = -2.0 * wtemp * wtemp;
            wpi = Math.sin(theta);

            wr = 1.0;
            wi = 0.0;

            for (m = 1; m < mmax; m += 2) {
                for (i = m; i <= n; i += istep) {
                    j = i + mmax;

                    tempr = (float)(wr * data[j - 1] - wi * data[j]);
                    tempi = (float)(wr * data[j] + wi * data[j - 1]);

                    data[j - 1] = data[i - 1] - tempr;
                    data[j] = data[i] - tempi;
                    data[i - 1] += tempr;
                    data[i] += tempi;
                }

                wr = (wtemp = wr) * wpr - wi * wpi + wr;
                wi = wi * wpr + wtemp * wpi + wi;
            }

            mmax = istep;
        }
    }

    public void onDataAvailable(float[] raw_data) {
        if (raw_data == null)
            return;

        pattern_data = new float[raw_data.length * 2];

        /* use complex numbers */
        for (int i = 0; i < pattern_data.length; i += 2) {
            pattern_data[i] = raw_data[i];
            pattern_data[i + 1] = 0;
        }

        fft(pattern_data);
    }

    public void speak(View view) {
        Intent intent = new Intent(RecognizerIntent.ACTION_RECOGNIZE_SPEECH);

        // Specify the calling package to identify your application
        intent.putExtra(RecognizerIntent.EXTRA_CALLING_PACKAGE, getClass()
                .getPackage().getName());

        // Display an hint to the user about what he should say.
        intent.putExtra(RecognizerIntent.EXTRA_PROMPT, R.string.speak_now);

        // Given an hint to the recognizer about what the user is going to say
        //There are two form of language model available
        //1.LANGUAGE_MODEL_WEB_SEARCH : For short phrases
        //2.LANGUAGE_MODEL_FREE_FORM  : If not sure about the words or phrases and its domain.
        intent.putExtra(RecognizerIntent.EXTRA_LANGUAGE_MODEL,
                RecognizerIntent.LANGUAGE_MODEL_FREE_FORM);

        startActivityForResult(intent, VOICE_RECOGNITION_REQUEST_CODE);
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        if (requestCode == VOICE_RECOGNITION_REQUEST_CODE)

            //If Voice recognition is successful then it returns RESULT_OK
            if(resultCode == RESULT_OK) {

                ArrayList<String> textMatchList = data
                        .getStringArrayListExtra(RecognizerIntent.EXTRA_RESULTS);

                if (!textMatchList.isEmpty()) {
                    EditText editText = (EditText)findViewById(R.id.recTextBox);
                    String myQuery = "";
                    myQuery += textMatchList.get(0);
                    editText.setText(myQuery, TextView.BufferType.EDITABLE);
                }
                //Result code for various error.
            }else if(resultCode == RecognizerIntent.RESULT_AUDIO_ERROR){
                showToastMessage("Audio Error");
            }else if(resultCode == RecognizerIntent.RESULT_CLIENT_ERROR){
                showToastMessage("Client Error");
            }else if(resultCode == RecognizerIntent.RESULT_NETWORK_ERROR){
                showToastMessage("Network Error");
            }else if(resultCode == RecognizerIntent.RESULT_NO_MATCH){
                showToastMessage("No Match");
            }else if(resultCode == RecognizerIntent.RESULT_SERVER_ERROR){
                showToastMessage("Server Error");
            }
        super.onActivityResult(requestCode, resultCode, data);
    }
    /**
     * Helper method to show the toast message
     **/
    void showToastMessage(String message){
        Toast.makeText(this, message, Toast.LENGTH_SHORT).show();
    }
}
